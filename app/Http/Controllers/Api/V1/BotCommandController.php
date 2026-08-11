<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotCommandController extends Controller
{
    public function slack(Request $request): JsonResponse
    {
        return response()->json([
            'response_type' => 'in_channel',
            'text' => $this->summary($request->string('text')->toString()),
        ]);
    }

    public function discord(Request $request): JsonResponse
    {
        return response()->json([
            'type' => 4,
            'data' => ['content' => $this->summary((string) $request->input('data.options.0.value', 'revenue'))],
        ]);
    }

    private function summary(string $command): string
    {
        $from = now()->startOfMonth();
        $revenue = (float) Sale::where('status', 'paid')->whereDate('sale_date', '>=', $from)->sum('total_revenue');
        $profit = (float) Sale::where('status', 'paid')->whereDate('sale_date', '>=', $from)->sum('total_profit');
        $expenses = (float) Expense::whereDate('expense_date', '>=', $from)->sum('amount');

        if (str_contains(strtolower($command), 'expense')) {
            return 'ProfitLens expenses this month: $' . number_format($expenses, 2);
        }

        if (str_contains(strtolower($command), 'profit')) {
            return 'ProfitLens profit this month: $' . number_format($profit, 2);
        }

        return 'ProfitLens revenue this month: $' . number_format($revenue, 2);
    }
}
