<?php

declare(strict_types=1);

use App\Models\Sale;
use App\Models\User;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
    $this->admin  = createTenantUser($this->tenant, 'admin', 'admin@acme.test');
    $this->member = createTenantUser($this->tenant, 'member', 'member@acme.test');
});

it('owner can drill into a teammate to see their stats and activity', function () {
    $this->tenant->run(function () {
        Sale::factory()->count(3)->create([
            'created_by'    => $this->member->id,
            'status'        => 'paid',
            'total_revenue' => 100,
        ]);
    });

    $this->actingAs($this->owner)
        ->get(tenantUrl('/team/' . $this->member->id))
        ->assertOk()
        ->assertSee($this->member->name)
        ->assertSee('Sales recorded')
        ->assertSee('3')               // sales count
        ->assertSee('$300.00');        // revenue
});

it('search filters the team list', function () {
    $this->tenant->run(function () {
        User::factory()->create(['name' => 'Alpha Engineer', 'email' => 'alpha@acme.test']);
        User::factory()->create(['name' => 'Beta Sales',     'email' => 'beta@acme.test']);
    });

    $this->actingAs($this->owner)
        ->get(tenantUrl('/team?q=Alpha'))
        ->assertOk()
        ->assertSee('Alpha Engineer')
        ->assertDontSee('Beta Sales');
});

it('owner can transfer ownership to another user', function () {
    $this->actingAs($this->owner)
        ->patch(tenantUrl('/team/' . $this->admin->id . '/transfer-ownership'), [
            'confirmation' => $this->admin->email,
        ])
        ->assertRedirect();

    $this->admin->refresh();
    $this->owner->refresh();
    expect($this->admin->hasRole('owner'))->toBeTrue()
        ->and($this->admin->hasRole('admin'))->toBeFalse()
        ->and($this->owner->hasRole('owner'))->toBeFalse()
        ->and($this->owner->hasRole('admin'))->toBeTrue();
});

it('transfer ownership rejects wrong email confirmation', function () {
    $this->actingAs($this->owner)
        ->patch(tenantUrl('/team/' . $this->admin->id . '/transfer-ownership'), [
            'confirmation' => 'wrong@email.test',
        ])
        ->assertSessionHasErrors('confirmation');

    expect($this->owner->fresh()->hasRole('owner'))->toBeTrue();
});

it('only an owner can initiate ownership transfer', function () {
    $this->actingAs($this->admin)
        ->patch(tenantUrl('/team/' . $this->member->id . '/transfer-ownership'), [
            'confirmation' => $this->member->email,
        ])
        ->assertStatus(403);
});

it('TrackLastSeen middleware updates last_seen_at on authed requests', function () {
    expect($this->owner->last_seen_at)->toBeNull();

    $this->actingAs($this->owner)
        ->get(tenantUrl('/'))
        ->assertOk();

    expect($this->owner->fresh()->last_seen_at)->not->toBeNull();
});

it('user-profile page is accessible to non-managers as read-only', function () {
    // Member can view a teammate's profile but with no Remove button.
    $this->actingAs($this->member)
        ->get(tenantUrl('/team/' . $this->admin->id))
        ->assertOk()
        ->assertSee($this->admin->name)
        ->assertDontSee('Remove from workspace');
});
