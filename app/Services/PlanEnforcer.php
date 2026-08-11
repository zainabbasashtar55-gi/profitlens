<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;

class PlanEnforcer
{
    public function __construct(private ?Tenant $tenant = null)
    {
        $this->tenant ??= tenant();
    }

    public function plan(): string
    {
        return $this->tenant?->plan ?? 'free';
    }

    public function limits(): array
    {
        return config("plans.plans.{$this->plan()}.limits", config('plans.plans.free.limits'));
    }

    public function limit(string $key): int
    {
        return (int) ($this->limits()[$key] ?? PHP_INT_MAX);
    }

    public function check(string $key): array
    {
        $current = $this->currentUsage($key);
        $limit   = $this->limit($key);

        return [
            'current'        => $current,
            'limit'          => $limit,
            'remaining'      => max(0, $limit - $current),
            'at_limit'       => $current >= $limit,
            'percent_used'   => $limit === PHP_INT_MAX ? 0 : min(100, round($current / max(1, $limit) * 100, 1)),
        ];
    }

    public function canAdd(string $key): bool
    {
        return ! $this->check($key)['at_limit'];
    }

    public function assertCanAdd(string $key, ?string $message = null): void
    {
        if (! $this->canAdd($key)) {
            $check = $this->check($key);
            $label = ucfirst(str_replace('_', ' ', $key));
            abort(402, $message ?? "Plan limit reached: {$label} ({$check['current']}/{$check['limit']}). Upgrade to add more.");
        }
    }

    public function currentUsage(string $key): int
    {
        return match ($key) {
            'users'           => User::count() + Invitation::whereNull('accepted_at')->count(),
            'sales_per_month' => Sale::whereBetween('sale_date', [
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
            ])->count(),
            'products'        => Product::count(),
            'storage_mb'      => $this->storageUsageMb(),
            default           => 0,
        };
    }

    public function summary(): array
    {
        return [
            'plan'   => $this->plan(),
            'limits' => collect(['users', 'sales_per_month', 'products', 'storage_mb'])
                ->mapWithKeys(fn ($k) => [$k => $this->check($k)])
                ->all(),
        ];
    }

    private function storageUsageMb(): int
    {
        // Approximate: sum of expense receipt file sizes in MB.
        $bytes = (int) Expense::query()
            ->whereNotNull('receipt_path')
            ->get()
            ->sum(function ($expense) {
                try {
                    return \Storage::disk(config('filesystems.receipts_disk'))->size($expense->receipt_path);
                } catch (\Throwable) {
                    return 0;
                }
            });

        return (int) round($bytes / 1_048_576);
    }
}
