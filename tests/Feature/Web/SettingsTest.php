<?php

declare(strict_types=1);

use App\Models\Tenant;
use Spatie\Activitylog\Models\Activity;
use Stancl\Tenancy\Database\Models\Domain;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
    $this->member = createTenantUser($this->tenant, 'member', 'member@acme.test');
});

it('owner can view the settings page', function () {
    $this->actingAs($this->owner)
        ->get(tenantUrl('/settings'))
        ->assertOk()
        ->assertSee('Workspace settings')
        ->assertSee('Danger zone');
});

it('non-owners get 403 on the settings page', function () {
    $this->actingAs($this->member)
        ->get(tenantUrl('/settings'))
        ->assertStatus(403);
});

it('owner can rename the workspace', function () {
    $this->actingAs($this->owner)
        ->patch(tenantUrl('/settings/name'), ['name' => 'Acme Renamed Inc'])
        ->assertRedirect();

    $this->tenant->refresh();
    expect($this->tenant->name)->toBe('Acme Renamed Inc');

    $log = Activity::where('description', 'like', '%renamed workspace%')->latest()->first();
    expect($log)->not->toBeNull();
});

it('rejects an empty workspace name', function () {
    $this->actingAs($this->owner)
        ->patch(tenantUrl('/settings/name'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('owner can change the subdomain and gets redirected to the new one', function () {
    $response = $this->actingAs($this->owner)
        ->patch(tenantUrl('/settings/subdomain'), ['subdomain' => 'renamed']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('renamed.profitlens.test');

    expect(Domain::where('tenant_id', $this->tenant->id)->first()->domain)
        ->toBe('renamed.profitlens.test');
});

it('blocks subdomain change when the new one is taken by another tenant', function () {
    // Set up a SECOND tenant that already owns "beta.profitlens.test"
    $beta = createTenant('beta', 'Beta Co', 'beta.profitlens.test');

    $this->actingAs($this->owner)
        ->patch(tenantUrl('/settings/subdomain'), ['subdomain' => 'beta'])
        ->assertSessionHasErrors('subdomain');

    // Original domain unchanged
    expect(Domain::where('tenant_id', $this->tenant->id)->first()->domain)
        ->toBe('acme.profitlens.test');
});

it('rejects subdomain with invalid characters', function () {
    $this->actingAs($this->owner)
        ->patch(tenantUrl('/settings/subdomain'), ['subdomain' => 'NOT VALID!'])
        ->assertSessionHasErrors('subdomain');
});

it('owner can delete the workspace with correct confirmation', function () {
    $tenantId   = $this->tenant->id;
    $tenantName = $this->tenant->name;

    $this->actingAs($this->owner)
        ->delete(tenantUrl('/settings'), ['confirmation' => $tenantName])
        ->assertRedirect();

    expect(Tenant::find($tenantId))->toBeNull();
});

it('refuses to delete with wrong confirmation', function () {
    $this->actingAs($this->owner)
        ->delete(tenantUrl('/settings'), ['confirmation' => 'Wrong Name'])
        ->assertSessionHasErrors('confirmation');

    // Tenant should still exist
    expect(Tenant::find($this->tenant->id))->not->toBeNull();
});

it('refuses to delete with empty confirmation', function () {
    $this->actingAs($this->owner)
        ->delete(tenantUrl('/settings'), ['confirmation' => ''])
        ->assertSessionHasErrors('confirmation');
});

it('non-owners cannot delete a workspace', function () {
    $this->actingAs($this->member)
        ->delete(tenantUrl('/settings'), ['confirmation' => $this->tenant->name])
        ->assertStatus(403);

    expect(Tenant::find($this->tenant->id))->not->toBeNull();
});
