<?php

declare(strict_types=1);

use App\Models\Customer;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
    $this->member = createTenantUser($this->tenant, 'member', 'member@acme.test');
});

it('lists customers paginated, scoped to tenant', function () {
    $this->tenant->run(fn () => Customer::factory()->count(3)->create());
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/customers'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'company', 'lifetime_value']], 'meta', 'links']);
});

it('creates a customer and returns 201', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/customers'), [
            'name'    => 'New Co',
            'email'   => 'hi@new.test',
            'company' => 'New Co Ltd',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'New Co');

    $this->tenant->run(fn () => expect(Customer::where('name', 'New Co')->exists())->toBeTrue());
});

it('rejects invalid customer with validation errors', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/customers'), ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);
});

it('lets owners delete a customer', function () {
    $customer = $this->tenant->run(fn () => Customer::factory()->create());
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->deleteJson(tenantUrl('/api/v1/customers/' . $customer->id))
        ->assertStatus(204);

    $this->tenant->run(fn () => expect(Customer::find($customer->id))->toBeNull());
});

it('blocks members from deleting a customer (403)', function () {
    $customer = $this->tenant->run(fn () => Customer::factory()->create());
    $token = loginAsApi($this->tenant, $this->member);

    $this->withToken($token)
        ->deleteJson(tenantUrl('/api/v1/customers/' . $customer->id))
        ->assertStatus(403);
});

it('searches customers by name', function () {
    $this->tenant->run(function () {
        Customer::factory()->create(['name' => 'Acme Corp']);
        Customer::factory()->create(['name' => 'Beta Industries']);
    });
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/customers?q=Acme'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Acme Corp');
});
