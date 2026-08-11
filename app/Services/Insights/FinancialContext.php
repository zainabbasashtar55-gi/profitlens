<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ProfitGoal;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Compiles a compact, factual snapshot of the workspace's finances. This is the
 * grounding data the AI chat reasons over — the model is told to answer using
 * only what's here, so it can't hallucinate numbers.
 */
class FinancialContext
{
    public function __construct(
        private CashFlowForecaster $forecaster,
        private AnomalyDetector $anomalies,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function snapshot(): array
    {
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $monthEnd = $now->endOfMonth();
        $prevStart = $monthStart->subMonth();
        $prevEnd = $prevStart->endOfMonth();

        $forecast = $this->forecaster->forecast();
        $anomalies = $this->anomalies->detect();
        $goal = ProfitGoal::currentMonthly();

        return [
            'today' => $now->toDateString(),
            'currency' => 'USD',
            'this_month' => $this->periodSummary($monthStart, $monthEnd),
            'last_month' => $this->periodSummary($prevStart, $prevEnd),
            'last_6_months' => $this->trend(),
            'expense_breakdown_mtd' => $this->expenseBreakdown($monthStart, $monthEnd),
            'top_customers' => $this->topCustomers(),
            'top_products' => $this->topProducts(),
            'recurring_expenses_monthly' => $forecast['recurring_monthly'],
            'cash_flow_forecast_90d' => [
                'projected_net' => $forecast['projected_net_90d'],
                'avg_daily_net' => $forecast['daily']['net'],
                'ending_cumulative' => $forecast['weeks'] === [] ? 0 : end($forecast['weeks'])['cumulative'],
            ],
            'anomalies' => array_map(fn ($a) => $a['message'], $anomalies['anomalies']),
            'profit_goal' => $goal ? $goal->progress() : null,
        ];
    }

    /**
     * @return array<string,float>
     */
    private function periodSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $revenue = (float) Sale::whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])->where('status', 'paid')->sum('total_revenue');
        $cogs = (float) Sale::whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])->where('status', 'paid')->sum('total_cost');
        $profit = (float) Sale::whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])->where('status', 'paid')->sum('total_profit');
        $expenses = (float) Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

        return [
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($profit, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => round($profit - $expenses, 2),
            'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function trend(): array
    {
        $start = CarbonImmutable::now()->subMonths(5)->startOfMonth();
        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $mStart = $start->addMonths($i);
            $mEnd = $mStart->endOfMonth();

            $revenue = (float) Sale::whereBetween('sale_date', [$mStart->toDateString(), $mEnd->toDateString()])->where('status', 'paid')->sum('total_revenue');
            $profit = (float) Sale::whereBetween('sale_date', [$mStart->toDateString(), $mEnd->toDateString()])->where('status', 'paid')->sum('total_profit');
            $expenses = (float) Expense::whereBetween('expense_date', [$mStart->toDateString(), $mEnd->toDateString()])->sum('amount');

            $months[] = [
                'month' => $mStart->format('M Y'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
                'expenses' => round($expenses, 2),
                'net' => round($profit - $expenses, 2),
            ];
        }

        return $months;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function expenseBreakdown(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('expenses.deleted_at')
            ->select(DB::raw("COALESCE(expense_categories.name, 'Uncategorized') as category"), DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'total' => round((float) $r->total, 2)])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function topCustomers(): array
    {
        return Customer::query()
            ->select('customers.name', DB::raw('COALESCE(SUM(sales.total_revenue),0) as revenue'))
            ->leftJoin('sales', function ($join) {
                $join->on('sales.customer_id', '=', 'customers.id')->where('sales.status', 'paid')->whereNull('sales.deleted_at');
            })
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'revenue' => round((float) $r->revenue, 2)])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function topProducts(): array
    {
        return SaleItem::query()
            ->select('product_name', DB::raw('SUM(line_total) as revenue'), DB::raw('SUM(line_profit) as profit'))
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->product_name, 'revenue' => round((float) $r->revenue, 2), 'profit' => round((float) $r->profit, 2)])
            ->all();
    }
}
