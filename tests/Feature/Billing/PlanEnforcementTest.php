<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\PlanEnforcer;

beforeEach(function () {
    $this->tenant = createTenant(); // free plan by default
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
});

it('free plan caps users at 3 (owner + invites)', function () {
    // Owner is user #1. Invite 2 more = 3 total. The 4th must be rejected.
    $this->actingAs($this->owner);

    $this->tenant->run(function () {
        $plan = new PlanEnforcer();
        expect($plan->limit('users'))->toBe(3);

        // 1 owner exists. Add 2 more users → 3 total → at limit.
        User::factory()->count(2)->create();
        expect($plan->check('users')['current'])->toBe(3)
            ->and($plan->canAdd('users'))->toBeFalse();
    });
});

it('blocks a 4th invitation on the free plan with a clear error', function () {
    $this->tenant->run(fn () => User::factory()->count(2)->create()); // 3 total

    $this->actingAs($this->owner);
    $this->withSession(['_token' => 'test'])
        ->post(tenantUrl('/invitations'), [
            '_token' => 'test',
            'email'  => 'fourth@acme.test',
            'role'   => 'member',
        ])
        ->assertSessionHasErrors(['email']);
});

it('free plan caps products at 25', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->tenant->run(fn () => Product::factory()->count(25)->create());

    $response = $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/products'), [
            'name'        => 'One Too Many',
            'cost_price'  => 10,
            'sell_price'  => 20,
        ]);

    $response->assertStatus(402); // Payment Required
});

it('pro plan raises the product cap to 1000', function () {
    $this->tenant->update(['plan' => 'pro']);
    $token = loginAsApi($this->tenant, $this->owner);

    $this->tenant->run(fn () => Product::factory()->count(25)->create());

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/products'), [
            'name'       => 'On Pro',
            'cost_price' => 10,
            'sell_price' => 20,
        ])
        ->assertStatus(201);
});

it('enterprise plan has unlimited everything', function () {
    $this->tenant->update(['plan' => 'enterprise']);

    $this->tenant->run(function () {
        $plan = new PlanEnforcer();
        expect($plan->limit('users'))->toBe(PHP_INT_MAX)
            ->and($plan->limit('products'))->toBe(PHP_INT_MAX)
            ->and($plan->limit('sales_per_month'))->toBe(PHP_INT_MAX);
    });
});

it('plan summary returns usage + percent for all limits', function () {
    $this->tenant->update(['plan' => 'free']);
    $this->tenant->run(fn () => Product::factory()->count(5)->create());

    $this->tenant->run(function () {
        $summary = (new PlanEnforcer())->summary();
        expect($summary['plan'])->toBe('free')
            ->and($summary['limits']['products']['current'])->toBe(5)
            ->and($summary['limits']['products']['limit'])->toBe(25)
            ->and($summary['limits']['products']['percent_used'])->toBe(20.0);
    });
});

it('owner can switch plan via billing checkout in dev mode', function () {
    $this->actingAs($this->owner);

    $this->post(tenantUrl('/billing/checkout'), ['plan' => 'pro'])
        ->assertRedirect(tenantUrl('/billing'));

    $this->tenant->refresh();
    expect($this->tenant->plan)->toBe('pro');
});

it('non-owners cannot access billing', function () {
    $member = createTenantUser($this->tenant, 'member', 'member@acme.test');
    $this->actingAs($member);

    $this->get(tenantUrl('/billing'))->assertStatus(403);
});
