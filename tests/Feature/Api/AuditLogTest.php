<?php

declare(strict_types=1);

use App\Models\Customer;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
});

it('records activity when a customer is created via the API', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/customers'), [
            'name'  => 'Audited Inc',
            'email' => 'hi@audited.test',
        ])
        ->assertStatus(201);

    $this->tenant->run(function () {
        $log = Activity::where('subject_type', Customer::class)->latest()->first();
        expect($log)->not->toBeNull()
            ->and($log->event)->toBe('created')
            ->and($log->description)->toContain('added customer Audited Inc');
    });
});

it('records activity with before/after diff on update', function () {
    $token = loginAsApi($this->tenant, $this->owner);
    $customer = $this->tenant->run(fn () => Customer::factory()->create(['name' => 'Original Name']));

    $this->withToken($token)
        ->putJson(tenantUrl('/api/v1/customers/' . $customer->id), ['name' => 'Renamed Co'])
        ->assertOk();

    $this->tenant->run(function () use ($customer) {
        $log = Activity::where('subject_id', $customer->id)->where('event', 'updated')->first();
        expect($log)->not->toBeNull()
            ->and($log->properties['attributes']['name'])->toBe('Renamed Co')
            ->and($log->properties['old']['name'])->toBe('Original Name');
    });
});

it('audit log lives in tenant DB, not shared across tenants', function () {
    $token = loginAsApi($this->tenant, $this->owner);
    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/customers'), ['name' => 'Acme-only', 'email' => 'a@a.test']);

    // A separate tenant must see zero activity from Acme.
    $beta = createTenant('beta', 'Beta', 'beta.profitlens.test');
    $beta->run(function () {
        expect(Activity::count())->toBe(0);
    });

    $this->tenant->run(function () {
        expect(Activity::count())->toBeGreaterThan(0);
    });
});
