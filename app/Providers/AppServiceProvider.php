<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BigSaleAlert;
use App\Events\ExpenseLogged;
use App\Events\SaleRecorded;
use App\Listeners\DispatchIntegrationWebhooks;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Cashier's "billable" model is the workspace, not the individual user.
        // A subscription belongs to the tenant — every member of that workspace
        // is implicitly covered.
        Cashier::useCustomerModel(Tenant::class);

        // Password-reset and email-verification links must point to the
        // tenant subdomain the request originated from — not APP_URL (which
        // is the central marketing domain). request()->getSchemeAndHttpHost()
        // resolves to e.g. http://acme.profitlens.test inside a tenant request.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return request()->getSchemeAndHttpHost() . '/reset-password/' . $token
                . '?email=' . urlencode($user->getEmailForPasswordReset());
        });

        VerifyEmail::createUrlUsing(function ($user) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
            );
        });

        // Owners bypass every gate. Uses hasRole (not $user->can) to avoid
        // recursion through the Gate system.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('owner') ? true : null;
        });

        // Spatie's HasRoles trait already makes $user->can('export-reports')
        // resolve against the permissions table. The explicit gate adds a
        // role-membership floor on top of the raw permission grant.
        Gate::define('export-reports', function (User $user) {
            return $user->hasAnyRole(['owner', 'admin'])
                && $user->hasPermissionTo('export-reports');
        });

        RateLimiter::for('api-public', function (Request $request) {
            $plan = tenant('plan') ?: 'free';
            $limit = config("plans.plans.{$plan}.limits.api_requests_per_minute", 60);

            return Limit::perMinute($limit)->by((string) tenant('id') . '|' . ($request->user()?->id ?? $request->ip()));
        });

        Event::listen(SaleRecorded::class, DispatchIntegrationWebhooks::class);
        Event::listen(BigSaleAlert::class, DispatchIntegrationWebhooks::class);
        Event::listen(ExpenseLogged::class, DispatchIntegrationWebhooks::class);
    }
}
