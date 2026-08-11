<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
});

it('creates a sale with line items and computes totals correctly', function () {
    $token = loginAsApi($this->tenant, $this->owner);
    [$customer, $product] = $this->tenant->run(fn () => [
        Customer::factory()->create(),
        Product::factory()->create(['cost_price' => 10, 'sell_price' => 25]),
    ]);

    $response = $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'customer_id' => $customer->id,
            'sale_date'   => '2026-05-01',
            'status'      => 'paid',
            'items'       => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity'   => 3,
                    'unit_price' => 25,
                    'unit_cost'  => 10,
                ],
            ],
        ])
        ->assertStatus(201);

    // 3 × 25 = 75 revenue. 3 × (25-10) = 45 profit.
    expect((float) $response->json('data.totals.revenue'))->toEqual(75.0)
        ->and((float) $response->json('data.totals.profit'))->toEqual(45.0);

    $this->tenant->run(function () {
        $sale = Sale::with('items')->first();
        expect((float) $sale->total_revenue)->toEqual(75.00)
            ->and((float) $sale->total_profit)->toEqual(45.00)
            ->and($sale->items)->toHaveCount(1);
    });
});

it('requires at least one line item', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'sale_date' => '2026-05-01',
            'status'    => 'paid',
            'items'     => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('rejects an unknown status', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'sale_date' => '2026-05-01',
            'status'    => 'bogus',
            'items'     => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10, 'unit_cost' => 5]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('filters sales by date range', function () {
    $token = loginAsApi($this->tenant, $this->owner);
    $this->tenant->run(function () {
        Sale::factory()->create(['sale_date' => '2026-01-15', 'status' => 'paid']);
        Sale::factory()->create(['sale_date' => '2026-05-15', 'status' => 'paid']);
    });

    $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/sales?from=2026-05-01&to=2026-05-31'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
