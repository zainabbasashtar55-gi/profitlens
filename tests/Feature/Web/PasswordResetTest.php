<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->user   = createTenantUser($this->tenant, 'owner', 'jane@acme.test');
});

it('shows the forgot password form', function () {
    $this->get(tenantUrl('/forgot-password'))
        ->assertOk()
        ->assertSee('Forgot your password?');
});

it('sends a reset link with a tenant-scoped URL', function () {
    Notification::fake();

    $this->post(tenantUrl('/forgot-password'), ['email' => 'jane@acme.test'])
        ->assertSessionHas('status');

    $this->tenant->run(function () {
        $user = User::where('email', 'jane@acme.test')->first();

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $action = collect($mail->actionUrl)->first() ?? $mail->actionUrl;
            expect((string) $action)->toContain('acme.profitlens.test')
                ->and((string) $action)->toContain('/reset-password/');
            return true;
        });
    });
});

it('does not reveal whether email exists (returns same response either way)', function () {
    Notification::fake();

    // Unknown email — should still get the generic message, no error.
    $this->post(tenantUrl('/forgot-password'), ['email' => 'nobody@nope.test'])
        ->assertSessionHas('status')
        ->assertSessionMissing('errors');
});

it('shows the reset form with token + email pre-filled', function () {
    $this->get(tenantUrl('/reset-password/some-token-here?email=jane@acme.test'))
        ->assertOk()
        ->assertSee('Set a new password')
        ->assertSee('jane@acme.test');
});

it('resets password with a valid token and redirects to login', function () {
    Event::fake([PasswordReset::class]);

    $token = $this->tenant->run(fn () => \Illuminate\Support\Facades\Password::createToken(User::first()));

    $this->post(tenantUrl('/reset-password'), [
        'token'                 => $token,
        'email'                 => 'jane@acme.test',
        'password'              => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertRedirect(tenantUrl('/login'));

    $this->tenant->run(function () {
        $user = User::where('email', 'jane@acme.test')->first();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    Event::assertDispatched(PasswordReset::class);
});

it('rejects reset with bad token', function () {
    $this->post(tenantUrl('/reset-password'), [
        'token'                 => 'invalid-token',
        'email'                 => 'jane@acme.test',
        'password'              => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('email');
});

it('rejects reset with mismatched confirmation', function () {
    $this->post(tenantUrl('/reset-password'), [
        'token'                 => 'whatever',
        'email'                 => 'jane@acme.test',
        'password'              => 'pw123456',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});
