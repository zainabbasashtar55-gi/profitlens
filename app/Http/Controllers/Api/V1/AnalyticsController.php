<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $now           = CarbonImmutable::now();
        $monthStart    = $now->startOfMonth();
        $monthEnd      = $now->endOfMonth();
        $prevStart     = $monthStart->subMonth();
        $prevEnd       = $prevStart->endOfMonth();

        $mtdRevenue  = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('total_revenue');
        $mtdProfit   = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('total_profit');
        $mtdExpenses = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');
        $mtdNet      = $mtdProfit - $mtdExpenses;

        $prevRevenue  = (float) Sale::whereBetween('sale_date', [$prevStart, $prevEnd])->where('status', 'paid')->sum('total_revenue');
        $prevProfit   = (float) Sale::whereBetween('sale_date', [$prevStart, $prevEnd])->where('status', 'paid')->sum('total_profit');
        $prevExpenses = (float) Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');
        $prevNet      = $prevProfit - $prevExpenses;

        return response()->json([
            'period' => [
                'month_start' => $monthStart->toDateString(),
                'month_end'   => $monthEnd->toDateString(),
            ],
            'kpis' => [
                'revenue'  => ['current' => $mtdRevenue,  'previous' => $prevRevenue,  'change_pct' => $this->changePct($mtdRevenue,  $prevRevenue)],
                'profit'   => ['current' => $mtdProfit,   'previous' => $prevProfit,   'change_pct' => $this->changePct($mtdProfit,   $prevProfit)],
                'expenses' => ['current' => $mtdExpenses, 'previous' => $prevExpenses, 'change_pct' => $this->changePct($mtdExpenses, $prevExpenses)],
                'net'      => ['current' => $mtdNet,      'previous' => $prevNet,      'change_pct' => $this->changePct($mtdNet,      $prevNet)],
                'margin_pct' => $mtdRevenue > 0 ? round($mtdProfit / $mtdRevenue * 100, 2) : 0,
            ],
            'counts' => [
                'customers'   => Customer::count(),
                'sales_mtd'   => Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->count(),
                'expenses_mtd'=> Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->count(),
            ],
            'top_customers' => $this->topCustomers(5),
            'top_products'  => $this->topProducts(5),
            'expense_breakdown' => $this->expenseBreakdown($monthStart, $monthEnd),
            'profit_trend'      => $this->profitTrend(),
            'recent_activity'   => $this->recentActivity(),
        ]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $from = $request->query('from')
            ? CarbonImmutable::parse($request->query('from'))
            : CarbonImmutable::now()->startOfMonth();
        $to = $request->query('to')
            ? CarbonImmutable::parse($request->query('to'))
            : CarbonImmutable::now()->endOfMonth();

        $revenue = (float) Sale::whereBetween('sale_date', [$from, $to])->where('status', 'paid')->sum('total_revenue');
        $cogs    = (float) Sale::whereBetween('sale_date', [$from, $to])->where('status', 'paid')->sum('total_cost');
        $grossProfit = $revenue - $cogs;

        $expensesByCategory = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$from, $to])
            ->whereNull('expenses.deleted_at')
            ->select('expense_categories.name as category', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->get();

        $totalExpenses = (float) $expensesByCategory->sum('total');
        $netProfit     = $grossProfit - $totalExpenses;

        return response()->json([
            'period' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'revenue'      => $revenue,
            'cogs'         => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $revenue > 0 ? round($grossProfit / $revenue * 100, 2) : 0,
            'operating_expenses' => $expensesByCategory->map(fn ($row) => [
                'category' => $row->category ?? 'Uncategorized',
                'total'    => (float) $row->total,
            ]),
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
            'net_margin_pct' => $revenue > 0 ? round($netProfit / $revenue * 100, 2) : 0,
        ]);
    }

    private function topCustomers(int $limit): \Illuminate\Support\Collection
    {
        return Customer::query()
            ->select('customers.id', 'customers.name', 'customers.company',
                     DB::raw('COALESCE(SUM(sales.total_revenue), 0) as revenue'),
                     DB::raw('COALESCE(SUM(sales.total_profit), 0) as profit'))
            ->leftJoin('sales', function ($join) {
                $join->on('sales.customer_id', '=', 'customers.id')
                     ->where('sales.status', 'paid')
                     ->whereNull('sales.deleted_at');
            })
            ->groupBy('customers.id', 'customers.name', 'customers.company')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id'      => $r->id,
                'name'    => $r->name,
                'company' => $r->company,
                'revenue' => (float) $r->revenue,
                'profit'  => (float) $r->profit,
            ]);
    }

    private function topProducts(int $limit): \Illuminate\Support\Collection
    {
        return SaleItem::query()
            ->select('product_name',
                     DB::raw('SUM(quantity) as qty_sold'),
                     DB::raw('SUM(line_total) as revenue'),
                     DB::raw('SUM(line_profit) as profit'))
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'name'     => $r->product_name,
                'qty_sold' => (int) $r->qty_sold,
                'revenue'  => (float) $r->revenue,
                'profit'   => (float) $r->profit,
            ]);
    }

    private function expenseBreakdown(CarbonImmutable $from, CarbonImmutable $to): \Illuminate\Support\Collection
    {
        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$from, $to])
            ->whereNull('expenses.deleted_at')
            ->select(
                'expense_categories.name as category',
                'expense_categories.color as color',
                DB::raw('SUM(expenses.amount) as total'),
            )
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category ?? 'Uncategorized',
                'color'    => $r->color ?? '#94a3b8',
                'total'    => (float) $r->total,
            ]);
    }

    private function profitTrend(): array
    {
        $start = CarbonImmutable::now()->subMonths(5)->startOfMonth();
        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $start->addMonths($i);
            $monthEnd   = $monthStart->endOfMonth();

            $revenue  = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('total_revenue');
            $profit   = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('total_profit');
            $expenses = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

            $months[] = [
                'label'    => $monthStart->format('M Y'),
                'revenue'  => $revenue,
                'profit'   => $profit,
                'expenses' => $expenses,
                'net'      => $profit - $expenses,
            ];
        }

        return $months;
    }

    private function recentActivity(): array
    {
        return \Spatie\Activitylog\Models\Activity::with('causer')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'event'       => $a->event,
                'subject'     => $a->subject_type ? class_basename($a->subject_type) : 'System',
                'description' => $a->description,
                'causer'      => $a->causer?->name ?? 'System',
                'at'          => $a->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function changePct(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current > 0 ? null : 0.0;
        }

        return round(($current - $previous) / abs($previous) * 100, 2);
    }
}
