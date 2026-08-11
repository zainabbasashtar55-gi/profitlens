<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\Sale;

it('returns dashboard KPIs computed only from current-month paid sales', function () {
    $tenant = createTenant();
    $owner  = createTenantUser($tenant, 'owner', 'owner@acme.test');

    $tenant->run(function () {
        Sale::factory()->create([
            'sale_date'     => now()->startOfMonth(),
            'status'        => 'paid',
            'total_revenue' => 100, 'total_cost' => 30, 'total_profit' => 70,
        ]);
        Sale::factory()->create([
            'sale_date'     => now()->startOfMonth(),
            'status'        => 'draft',          // should be excluded
            'total_revenue' => 999, 'total_cost' => 999, 'total_profit' => 999,
        ]);
        Expense::factory()->create([
            'expense_date' => now()->startOfMonth(),
            'amount'       => 20,
        ]);
    });

    $token = loginAsApi($tenant, $owner);

    $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/analytics/dashboard'))
        ->assertOk()
        ->assertJsonPath('kpis.revenue.current',  100)
        ->assertJsonPath('kpis.profit.current',    70)
        ->assertJsonPath('kpis.expenses.current',  20)
        ->assertJsonPath('kpis.net.current',       50);
});

it('returns a profit and loss statement', function () {
    $tenant = createTenant();
    $owner  = createTenantUser($tenant, 'owner', 'owner@acme.test');

    $tenant->run(function () {
        Sale::factory()->create([
            'sale_date'     => '2026-05-10',
            'status'        => 'paid',
            'total_revenue' => 1000, 'total_cost' => 400, 'total_profit' => 600,
        ]);
        Expense::factory()->create(['expense_date' => '2026-05-12', 'amount' => 150]);
    });

    $token = loginAsApi($tenant, $owner);

    $response = $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/analytics/profit-loss?from=2026-05-01&to=2026-05-31'))
        ->assertOk();

    expect((float) $response->json('revenue'))->toEqual(1000.0)
        ->and((float) $response->json('cogs'))->toEqual(400.0)
        ->and((float) $response->json('gross_profit'))->toEqual(600.0)
        ->and((float) $response->json('total_expenses'))->toEqual(150.0)
        ->and((float) $response->json('net_profit'))->toEqual(450.0);
});
