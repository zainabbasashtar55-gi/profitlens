<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSignupController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'subdomain'      => ['required', 'string', 'alpha_dash:ascii', 'lowercase', 'max:63', 'unique:domains,domain'],
            'plan'           => ['nullable', 'string', 'in:free,pro,enterprise'],
            'owner_name'     => ['required', 'string', 'max:120'],
            'owner_email'    => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $chosenPlan = $data['plan'] ?? 'free';

        $tenant = Tenant::create([
            'id'   => Str::slug($data['subdomain']),
            'name' => $data['name'],
            // Until paid plans are confirmed by Stripe (webhook), the tenant
            // starts on free. The webhook flips it to the paid plan once the
            // Checkout session completes. In dev mode (no Stripe keys) the
            // chosen plan is applied immediately.
            'plan' => config('cashier.key') ? 'free' : $chosenPlan,
        ]);

        $fullDomain = $data['subdomain'] . '.' . config('app.tenant_domain');
        $tenant->domains()->create(['domain' => $fullDomain]);

        $tenant->run(function () use ($data) {
            $owner = User::create([
                'name'              => $data['owner_name'],
                'email'             => $data['owner_email'],
                'password'          => Hash::make($data['owner_password']),
                'email_verified_at' => now(),
            ]);
            $owner->assignRole('owner');
        });

        $scheme = $request->getScheme();
        $port   = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':' . $port;
        $loginUrl = "{$scheme}://{$fullDomain}{$portSuffix}/login?email=" . urlencode($data['owner_email']);

        // Real Stripe path: chosen a paid plan AND Stripe is configured → kick
        // them straight into Stripe Checkout. Once they pay, the webhook
        // upgrades their plan and they hit the success URL which forwards
        // them into the tenant.
        $priceId = config("plans.plans.{$chosenPlan}.stripe_price");
        if (config('cashier.key') && $chosenPlan !== 'free' && $priceId) {
            try {
                $session = $tenant->newSubscription('default', $priceId)->checkout([
                    'success_url' => $loginUrl . '&stripe=success',
                    'cancel_url'  => "{$scheme}://" . config('tenancy.central_domains.0', 'localhost') . "{$portSuffix}/billing?stripe=cancelled",
                    'customer_email' => $data['owner_email'],
                ]);

                return redirect()->away($session->url);
            } catch (\Throwable $e) {
                // If Stripe call fails (bad keys, bad price ID), fall back to
                // dev-mode so signup doesn't appear broken — but warn the owner.
                report($e);
                $tenant->update(['plan' => $chosenPlan]);
                return redirect()->away($loginUrl)
                    ->with('status', 'Workspace created (Stripe error — falling back to dev mode). Log in to continue.');
            }
        }

        return redirect()->away($loginUrl)
            ->with('status', 'Workspace created. Log in with the password you just set.');
    }
}
