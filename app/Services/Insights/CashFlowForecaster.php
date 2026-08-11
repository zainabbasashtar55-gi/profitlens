<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Expense;
use App\Models\Sale;
use Carbon\CarbonImmutable;

/**
 * Projects cash flow for the next 90 days.
 *
 * Deterministic by design — forecasting money with a rule you can audit beats a
 * black-box guess. Two drivers:
 *   • Inflow  = trailing-90-day sales velocity (avg daily paid revenue).
 *   • Outflow = recurring expenses (normalised to a daily rate) + trailing-90-day
 *               average of variable (non-recurring) spend.
 *
 * Returns weekly buckets with a running cumulative net so the UI can chart the
 * projected cash trajectory.
 */
class CashFlowForecaster
{
    private const HORIZON_DAYS = 90;

    private const WEEKS = 13;

    /**
     * @return array<string,mixed>
     */
    public function forecast(): array
    {
        $now = CarbonImmutable::now();
        $winStart = $now->subDays(90);

        $revenue90 = (float) Sale::whereBetween('sale_date', [$winStart->toDateString(), $now->toDateString()])
            ->where('status', 'paid')
            ->sum('total_revenue');

        $variable90 = (float) Expense::whereBetween('expense_date', [$winStart->toDateString(), $now->toDateString()])
            ->where('recurring', false)
            ->sum('amount');

        $recurringMonthly = $this->recurringMonthly();

        $dailyRevenue = $revenue90 / 90.0;
        $dailyVariable = $variable90 / 90.0;
        $dailyRecurring = $recurringMonthly * 12.0 / 365.0;
        $dailyOutflow = $dailyVariable + $dailyRecurring;
        $dailyNet = $dailyRevenue - $dailyOutflow;

        $weeks = [];
        $cumulative = 0.0;
        for ($i = 0; $i < self::WEEKS; $i++) {
            $weekStart = $now->addDays($i * 7);
            $inflow = $dailyRevenue * 7;
            $outflow = $dailyOutflow * 7;
            $net = $dailyNet * 7;
            $cumulative += $net;

            $weeks[] = [
                'label' => 'Wk '.($i + 1),
                'date' => $weekStart->format('M j'),
                'inflow' => round($inflow, 2),
                'outflow' => round($outflow, 2),
                'net' => round($net, 2),
                'cumulative' => round($cumulative, 2),
            ];
        }

        return [
            'horizon_days' => self::HORIZON_DAYS,
            'daily' => [
                'revenue' => round($dailyRevenue, 2),
                'recurring' => round($dailyRecurring, 2),
                'variable' => round($dailyVariable, 2),
                'outflow' => round($dailyOutflow, 2),
                'net' => round($dailyNet, 2),
            ],
            'recurring_monthly' => round($recurringMonthly, 2),
            'projected_net_90d' => round($dailyNet * self::HORIZON_DAYS, 2),
            'weeks' => $weeks,
            'has_data' => $revenue90 > 0 || $variable90 > 0 || $recurringMonthly > 0,
            'note' => 'Projection assumes your trailing-90-day sales velocity holds and recurring expenses continue. Not a guarantee.',
        ];
    }

    /**
     * Normalise every recurring expense to a monthly figure.
     */
    private function recurringMonthly(): float
    {
        $rows = Expense::where('recurring', true)->get(['amount', 'recurring_period']);

        $monthly = 0.0;
        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $monthly += match (strtolower((string) $row->recurring_period)) {
                'weekly' => $amount * 52 / 12,
                'quarterly' => $amount / 3,
                'yearly', 'annual', 'annually' => $amount / 12,
                default => $amount, // treat unknown/monthly as monthly
            };
        }

        return $monthly;
    }
}
