<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ProfitGoal;
use App\Services\Ai\ClaudeClient;
use App\Services\Insights\AnomalyDetector;
use App\Services\Insights\CashFlowForecaster;
use App\Services\Insights\FinancialContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsightsController extends Controller
{
    /**
     * The AI Insights workspace: forecast chart, anomaly flags, goal tracker,
     * and the "ask your numbers" chat box.
     */
    public function index(CashFlowForecaster $forecaster, AnomalyDetector $detector, ClaudeClient $claude): View
    {
        $goal = ProfitGoal::currentMonthly();

        return view('tenant.insights.index', [
            'forecast' => $forecaster->forecast(),
            'anomalies' => $detector->detect(),
            'goal' => $goal,
            'goalProgress' => $goal?->progress(),
            'aiEnabled' => $claude->enabled(),
        ]);
    }

    /**
     * Answer a natural-language question about the workspace's finances.
     */
    public function chat(Request $request, FinancialContext $context, ClaudeClient $claude): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
        ]);

        $snapshot = $context->snapshot();

        if (! $claude->enabled()) {
            return response()->json([
                'answer' => $this->fallbackAnswer($snapshot),
                'fallback' => true,
            ]);
        }

        $system = "You are ProfitLens AI, a sharp, friendly financial analyst for a small business owner.\n"
            ."Answer the user's question using ONLY the financial data provided below as JSON. "
            .'Be concise and specific — cite the actual numbers, format money as $ with thousands separators, '
            ."and call out the 'why' (which categories, customers, or trends drive the answer). "
            ."If the data doesn't contain what's needed, say so plainly rather than guessing.\n\n"
            ."FINANCIAL DATA (all amounts USD):\n"
            .json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Rebuild the short conversation, then append the new question.
        $messages = [];
        foreach ($validated['history'] ?? [] as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        $answer = $claude->complete(
            model: $claude->model('chat'),
            messages: $messages,
            system: $system,
            maxTokens: 1024,
            extra: ['thinking' => ['type' => 'adaptive']],
            timeout: 90,
        );

        if ($answer === null) {
            return response()->json([
                'answer' => "I couldn't reach the analysis engine just now. Here's a quick summary instead:\n\n".$this->fallbackAnswer($snapshot),
                'fallback' => true,
            ]);
        }

        return response()->json(['answer' => $answer, 'fallback' => false]);
    }

    /**
     * Deterministic summary used when the AI is unavailable.
     *
     * @param  array<string,mixed>  $s
     */
    private function fallbackAnswer(array $s): string
    {
        $m = $s['this_month'];
        $p = $s['last_month'];
        $fmt = fn ($n) => '$'.number_format((float) $n, 0);

        $netDir = $m['net_profit'] >= $p['net_profit'] ? 'up from' : 'down from';

        $lines = [
            "This month so far: revenue {$fmt($m['revenue'])}, expenses {$fmt($m['expenses'])}, net profit {$fmt($m['net_profit'])} ({$netDir} {$fmt($p['net_profit'])} last month).",
            "Gross margin is {$m['margin_pct']}%.",
        ];

        if (! empty($s['anomalies'])) {
            $lines[] = 'Worth a look: '.implode(' ', $s['anomalies']);
        }
        $lines[] = '90-day projected net cash flow: '.$fmt($s['cash_flow_forecast_90d']['projected_net']).'.';
        $lines[] = '(Add an ANTHROPIC_API_KEY to ask free-form questions.)';

        return implode("\n", $lines);
    }
}
