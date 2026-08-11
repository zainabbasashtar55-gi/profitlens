<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('tenant.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Password::sendResetLink looks up the user by email on the default
        // (= tenant) connection, generates a token, stores it in
        // password_reset_tokens, and dispatches the ResetPassword notification.
        $status = Password::sendResetLink($request->only('email'));

        // Always respond with a generic success message — never reveal whether
        // the email exists in this workspace (prevents enumeration).
        return back()->with('status', __('If that email belongs to a member of this workspace, a reset link is on the way.'));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('tenant.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('tenant.login')->with('status', __('Password reset. Log in with your new password.'))
            : back()->withErrors(['email' => __($status)]);
    }
}
