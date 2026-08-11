<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Expense;
use App\Services\Ai\ClaudeClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Flags spending anomalies — "your software spend tripled this month".
 *
 * Compares each expense category's month-to-date spend against its average over
 * the previous three full months. The detection is purely statistical (works
 * with no API key); when Claude is configured it adds a short plain-English
 * summary on top of the numbers.
 */
class AnomalyDetector
{
    /** Spend must be at least this many × the baseline to flag. */
    private const RATIO_THRESHOLD = 2.0;

    /** Ignore categories below this MTD spend (noise floor). */
    private const MIN_CURRENT = 50.0;

    /** For brand-new spend (no baseline), only flag above this. */
    private const MIN_NEW = 200.0;

    public function __construct(private ClaudeClient $claude) {}

    /**
     * @return array{anomalies: array<int,array<string,mixed>>, summary: ?string, baseline_months: int}
     */
    public function detect(): array
    {
        $now = CarbonImmutable::now();
        $currentStart = $now->startOfMonth();
        $currentEnd = $now->endOfMonth();
        $baseStart = $currentStart->subMonths(3);
        $baseEnd = $currentStart->subDay();

        $current = $this->spendByCategory($currentStart, $currentEnd);
        $baseline = $this->spendByCategory($baseStart, $baseEnd);

        $anomalies = [];
        foreach ($current as $category => $amount) {
            $baseAvg = isset($baseline[$category]) ? $baseline[$category] / 3.0 : 0.0;

            $isAnomaly = $baseAvg > 0
                ? ($amount >= self::MIN_CURRENT && ($amount / $baseAvg) >= self::RATIO_THRESHOLD)
                : ($amount >= self::MIN_NEW);

            if (! $isAnomaly) {
                continue;
            }

            $ratio = $baseAvg > 0 ? round($amount / $baseAvg, 1) : null;

            $anomalies[] = [
                'category' => $category,
                'current' => round($amount, 2),
                'baseline' => round($baseAvg, 2),
                'ratio' => $ratio,
                'delta' => round($amount - $baseAvg, 2),
                'severity' => ($ratio === null || $ratio >= 3.0) ? 'high' : 'medium',
                'message' => $this->describe($category, $amount, $baseAvg, $ratio),
            ];
        }

        usort($anomalies, fn ($a, $b) => ($b['ratio'] ?? 99) <=> ($a['ratio'] ?? 99));

        return [
            'anomalies' => $anomalies,
            'summary' => $this->summarize($anomalies),
            'baseline_months' => 3,
        ];
    }

    /**
     * @return array<string,float> category name => total spend
     */
    private function spendByCategory(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('expenses.deleted_at')
            ->select(
                DB::raw("COALESCE(expense_categories.name, 'Uncategorized') as category"),
                DB::raw('SUM(expenses.amount) as total'),
            )
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function describe(string $category, float $current, float $baseAvg, ?float $ratio): string
    {
        $cur = '$'.number_format($current, 0);

        if ($ratio === null) {
            return "New {$category} spend this month: {$cur} (no prior 3-month history).";
        }

        $base = '$'.number_format($baseAvg, 0);

        return "{$category} spend is {$ratio}× your 3-month average ({$cur} vs {$base}).";
    }

    /**
     * @param  array<int,array<string,mixed>>  $anomalies
     */
    private function summarize(array $anomalies): ?string
    {
        if ($anomalies === []) {
            return 'No unusual spending detected this month — everything is within normal range.';
        }

        // Deterministic summary; upgraded to a tighter sentence when AI is on.
        $fallback = count($anomalies) === 1
            ? $anomalies[0]['message']
            : count($anomalies).' categories are running above their usual range this month. Review the flagged items below.';

        if (! $this->claude->enabled()) {
            return $fallback;
        }

        $lines = implode("\n", array_map(fn ($a) => '- '.$a['message'], $anomalies));

        $summary = $this->claude->complete(
            model: $this->claude->model('chat'),
            messages: [['role' => 'user', 'content' => "Spending anomalies detected:\n{$lines}\n\nWrite one concise, friendly sentence (max 30 words) summarizing what the owner should review. No preamble."]],
            system: 'You are a concise financial assistant for a small business owner.',
            maxTokens: 120,
            extra: ['thinking' => ['type' => 'adaptive']],
            timeout: 30,
        );

        return $summary ?: $fallback;
    }
}
