<?php

declare(strict_types=1);

use App\Models\Customer;

it('does not leak customers across tenants', function () {
    $acme  = createTenant('acme', 'Acme', 'acme.profitlens.test');
    $beta  = createTenant('beta', 'Beta', 'beta.profitlens.test');
    $acmeOwner = createTenantUser($acme, 'owner', 'owner@acme.test');
    $betaOwner = createTenantUser($beta, 'owner', 'owner@beta.test');

    $acme->run(fn () => Customer::factory()->create(['name' => 'ACME-CUSTOMER']));
    $beta->run(fn () => Customer::factory()->create(['name' => 'BETA-CUSTOMER']));

    $acmeToken = loginAsApi($acme, $acmeOwner, 'acme.profitlens.test');
    $betaToken = loginAsApi($beta, $betaOwner, 'beta.profitlens.test');

    $acmeResp = $this->withToken($acmeToken)
        ->getJson(tenantUrl('/api/v1/customers', 'acme.profitlens.test'));
    $betaResp = $this->withToken($betaToken)
        ->getJson(tenantUrl('/api/v1/customers', 'beta.profitlens.test'));

    $acmeNames = collect($acmeResp->json('data'))->pluck('name');
    $betaNames = collect($betaResp->json('data'))->pluck('name');

    expect($acmeNames)->toContain('ACME-CUSTOMER')->not->toContain('BETA-CUSTOMER');
    expect($betaNames)->toContain('BETA-CUSTOMER')->not->toContain('ACME-CUSTOMER');
});

it('rejects an Acme token when used against Beta\'s subdomain', function () {
    $acme = createTenant('acme', 'Acme', 'acme.profitlens.test');
    $beta = createTenant('beta', 'Beta', 'beta.profitlens.test');
    $acmeOwner = createTenantUser($acme, 'owner', 'owner@acme.test');

    $acmeToken = loginAsApi($acme, $acmeOwner, 'acme.profitlens.test');

    // The Acme token row lives in Acme's DB, not Beta's, so Beta sees no
    // matching token and returns 401.
    $this->withToken($acmeToken)
        ->getJson(tenantUrl('/api/v1/customers', 'beta.profitlens.test'))
        ->assertStatus(401);
});
