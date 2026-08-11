<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->tenant = createTenant();
});

it('central signup auto-verifies the new owner email', function () {
    // Simulate the central signup flow end-to-end.
    $this->post('http://profitlens.test/signup', [
        'name'           => 'New Co',
        'subdomain'      => 'newco',
        'plan'           => 'free',
        'owner_name'     => 'Owner',
        'owner_email'    => 'owner@newco.test',
        'owner_password' => 'password123',
    ])->assertRedirect();

    $newTenant = \App\Models\Tenant::find('newco');
    $newTenant->run(function () {
        $owner = User::where('email', 'owner@newco.test')->first();
        expect($owner)->not->toBeNull()
            ->and($owner->hasVerifiedEmail())->toBeTrue();
    });
});

it('shows verify-email notice when an unverified user accesses it', function () {
    $unverified = $this->tenant->run(function () {
        $user = User::create([
            'name'              => 'New User',
            'email'             => 'new@acme.test',
            'password'          => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
        $user->assignRole('member');
        return $user;
    });

    $this->actingAs($unverified)
        ->get(tenantUrl('/email/verify'))
        ->assertOk()
        ->assertSee('Verify your email');
});

it('marks user verified when they hit the verify endpoint with matching hash', function () {
    Event::fake([Verified::class]);

    $unverified = $this->tenant->run(function () {
        $user = User::create([
            'name'              => 'Unverified',
            'email'             => 'pending@acme.test',
            'password'          => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
        $user->assignRole('member');
        return $user;
    });

    // Skip the 'signed' middleware in the test — we're verifying the controller
    // logic, not Laravel's URL signing (which is well-tested upstream).
    $this->actingAs($unverified)
        ->withoutMiddleware(\Illuminate\Routing\Middleware\ValidateSignature::class)
        ->get(tenantUrl('/email/verify/' . $unverified->id . '/' . sha1($unverified->email)))
        ->assertRedirect();

    $this->tenant->run(function () {
        expect(User::where('email', 'pending@acme.test')->first()->hasVerifiedEmail())->toBeTrue();
    });
    Event::assertDispatched(Verified::class);
});

it('rejects a tampered verification URL with 403', function () {
    $unverified = $this->tenant->run(function () {
        $user = User::create([
            'name'              => 'Tamper Target',
            'email'             => 'tamper@acme.test',
            'password'          => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
        $user->assignRole('member');
        return $user;
    });

    $this->actingAs($unverified)
        ->get(tenantUrl('/email/verify/' . $unverified->id . '/wrong-hash'))
        ->assertStatus(403);
});
