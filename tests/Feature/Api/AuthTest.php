<?php

declare(strict_types=1);

it('issues a token on valid credentials and returns user + tenant', function () {
    $tenant = createTenant();
    $user   = createTenantUser($tenant, 'owner', 'jane@acme.test');

    $response = $this->postJson(tenantUrl('/api/v1/auth/login'), [
        'email'    => 'jane@acme.test',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'expires_at',
            'user'   => ['id', 'name', 'email', 'roles'],
            'tenant' => ['id', 'name', 'plan'],
        ])
        ->assertJsonPath('user.email', 'jane@acme.test')
        ->assertJsonPath('user.roles.0', 'owner')
        ->assertJsonPath('tenant.id', $tenant->id);
});

it('rejects bad credentials with 422', function () {
    $tenant = createTenant();
    createTenantUser($tenant, 'owner', 'jane@acme.test');

    $response = $this->postJson(tenantUrl('/api/v1/auth/login'), [
        'email'    => 'jane@acme.test',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('blocks unauthenticated access to protected endpoints', function () {
    createTenant();

    $this->getJson(tenantUrl('/api/v1/customers'))
        ->assertStatus(401);
});

it('returns me() with permissions for an authed user', function () {
    $tenant = createTenant();
    $user   = createTenantUser($tenant, 'admin', 'admin@acme.test');
    $token  = loginAsApi($tenant, $user);

    $this->withToken($token)
        ->getJson(tenantUrl('/api/v1/auth/me'))
        ->assertOk()
        ->assertJsonPath('email', 'admin@acme.test')
        ->assertJsonPath('roles.0', 'admin')
        ->assertJsonStructure(['permissions']);
});
