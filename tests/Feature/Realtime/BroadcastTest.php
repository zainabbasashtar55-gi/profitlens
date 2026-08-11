<?php

declare(strict_types=1);

use App\Events\BigSaleAlert;
use App\Events\ExpenseLogged;
use App\Events\InvitationAccepted;
use App\Events\LowCashWarning;
use App\Events\SaleRecorded;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\Product;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
});

it('broadcasts SaleRecorded on the tenant activity channel', function () {
    Event::fake([SaleRecorded::class, BigSaleAlert::class]);

    $token = loginAsApi($this->tenant, $this->owner);
    [$customer, $product] = $this->tenant->run(fn () => [
        Customer::factory()->create(),
        Product::factory()->create(['cost_price' => 5, 'sell_price' => 20]),
    ]);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'customer_id' => $customer->id,
            'sale_date'   => now()->toDateString(),
            'status'      => 'paid',
            'items'       => [['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 2, 'unit_price' => 20, 'unit_cost' => 5]],
        ])
        ->assertStatus(201);

    Event::assertDispatched(SaleRecorded::class, function ($event) {
        $channels = $event->broadcastOn();
        return $channels[0]->name === "private-tenant.{$this->tenant->id}.activity"
            && $event->broadcastAs() === 'sale.recorded';
    });
});

it('broadcasts BigSaleAlert privately to every owner on a record-breaking sale', function () {
    Event::fake([BigSaleAlert::class]);

    $token = loginAsApi($this->tenant, $this->owner);
    $product = $this->tenant->run(fn () => Product::factory()->create(['cost_price' => 1, 'sell_price' => 9999]));

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'sale_date' => now()->toDateString(),
            'status'    => 'paid',
            'items'     => [['product_id' => $product->id, 'product_name' => 'Huge', 'quantity' => 1, 'unit_price' => 9999, 'unit_cost' => 1]],
        ])
        ->assertStatus(201);

    Event::assertDispatched(BigSaleAlert::class, function ($event) {
        return $event->broadcastOn()[0]->name === "private-user.{$this->owner->id}"
            && $event->broadcastAs() === 'sale.big';
    });
});

it('broadcasts ExpenseLogged on the tenant activity channel', function () {
    Event::fake([ExpenseLogged::class]);

    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/expenses'), [
            'description'  => 'Server bill',
            'amount'       => 75,
            'expense_date' => now()->toDateString(),
        ])
        ->assertStatus(201);

    Event::assertDispatched(ExpenseLogged::class, function ($event) {
        return $event->broadcastOn()[0]->name === "private-tenant.{$this->tenant->id}.activity"
            && $event->broadcastAs() === 'expense.logged';
    });
});

it('broadcasts InvitationAccepted when an invite is accepted', function () {
    Event::fake([InvitationAccepted::class]);

    $invitation = $this->tenant->run(fn () => Invitation::create([
        'email'      => 'newbie@acme.test',
        'role'       => 'member',
        'invited_by' => $this->owner->id,
    ]));

    $this->post(tenantUrl('/invitations/' . $invitation->token), [
        'name'                  => 'Newbie',
        'password'              => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])->assertRedirect();

    Event::assertDispatched(InvitationAccepted::class, function ($event) {
        return $event->broadcastOn()[0]->name === "private-tenant.{$this->tenant->id}.team"
            && $event->broadcastAs() === 'invitation.accepted'
            && $event->role === 'member';
    });
});

it('payload of SaleRecorded contains actor, customer, revenue, profit', function () {
    Event::fake([SaleRecorded::class]);

    $token = loginAsApi($this->tenant, $this->owner);
    $customer = $this->tenant->run(fn () => Customer::factory()->create(['name' => 'TestCorp']));

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/sales'), [
            'customer_id' => $customer->id,
            'sale_date'   => now()->toDateString(),
            'status'      => 'paid',
            'items'       => [['product_name' => 'Widget', 'quantity' => 3, 'unit_price' => 10, 'unit_cost' => 4]],
        ])
        ->assertStatus(201);

    Event::assertDispatched(SaleRecorded::class, function ($event) {
        $payload = $event->broadcastWith();
        return $payload['customer'] === 'TestCorp'
            && (float) $payload['revenue'] === 30.0
            && (float) $payload['profit']  === 18.0
            && $payload['actor'] === $this->owner->name;
    });
});
