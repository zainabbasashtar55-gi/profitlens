<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Tenant test helpers
|--------------------------------------------------------------------------
*/

function createTenant(string $id = 'acme', string $name = 'Acme Inc', ?string $domain = null): Tenant
{
    $domain = $domain ?? $id . '.profitlens.test';

    $tenant = Tenant::create(['id' => $id, 'name' => $name, 'plan' => 'free']);
    $tenant->domains()->create(['domain' => $domain]);

    return $tenant;
}

function tenantDomainOf(Tenant $tenant): string
{
    return $tenant->domains()->first()->domain;
}

function createTenantUser(Tenant $tenant, string $role = 'owner', string $email = null): User
{
    return $tenant->run(function () use ($role, $email) {
        $user = User::create([
            'name'     => ucfirst($role) . ' User',
            'email'    => $email ?? ($role . '-' . uniqid() . '@acme.test'),
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    });
}

function tenantUrl(string $path = '/', string $domain = 'acme.profitlens.test'): string
{
    return 'http://' . $domain . '/' . ltrim($path, '/');
}

function loginAsApi(Tenant $tenant, User $user, string $domain = 'acme.profitlens.test'): string
{
    $response = test()->postJson(tenantUrl('/api/v1/auth/login', $domain), [
        'email'    => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk();

    return $response->json('token');
}
