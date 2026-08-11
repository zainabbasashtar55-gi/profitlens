<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A net-profit target for a period (currently monthly).
 *
 * "Set $10K profit this month" → a row here; progress is computed live from
 * paid-sale profit minus expenses over the period — the same definition as the
 * dashboard's Net Profit KPI, so the numbers always agree.
 */
class ProfitGoal extends Model
{
    protected $fillable = [
        'period_type',
        'period_start',
        'target_amount',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'target_amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The monthly goal covering today, if one is set.
     */
    public static function currentMonthly(): ?self
    {
        return static::query()
            ->where('period_type', 'month')
            ->whereDate('period_start', CarbonImmutable::now()->startOfMonth()->toDateString())
            ->first();
    }

    public function periodStart(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->period_start)->startOfDay();
    }

    public function periodEnd(): CarbonImmutable
    {
        return $this->periodStart()->endOfMonth();
    }

    /**
     * Live progress against the target.
     *
     * @return array{target: float, current: float, pct: float, remaining: float, period_label: string, on_track: bool}
     */
    public function progress(): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        $profit = (float) Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'paid')
            ->sum('total_profit');
        $expenses = (float) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $current = round($profit - $expenses, 2);
        $target = (float) $this->target_amount;
        $pct = $target > 0 ? round(max(0, $current) / $target * 100, 1) : 0.0;

        // On track = pace so far would hit the target by period end.
        $now = CarbonImmutable::now();
        $daysElapsed = max(1, $start->diffInDays($now->min($end)) + 1);
        $daysInPeriod = $start->diffInDays($end) + 1;
        $expectedPct = $daysInPeriod > 0 ? ($daysElapsed / $daysInPeriod * 100) : 100;

        return [
            'target' => round($target, 2),
            'current' => $current,
            'pct' => $pct,
            'remaining' => round(max(0, $target - $current), 2),
            'period_label' => $start->format('F Y'),
            'on_track' => $pct >= $expectedPct,
        ];
    }
}
