<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\PlanEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(PlanEnforcer $plan): View
    {
        abort_unless(auth()->user()->hasRole('owner'), 403, 'Only the workspace owner can manage billing.');

        return view('tenant.billing.index', [
            'tenant'      => tenant(),
            'currentPlan' => $plan->plan(),
            'plans'       => config('plans.plans'),
            'summary'     => $plan->summary(),
            'stripeKey'   => config('cashier.key'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('owner'), 403);

        $data = $request->validate([
            'plan' => ['required', Rule::in(['free', 'pro', 'enterprise'])],
        ]);

        $tenant = tenant();

        // Two paths:
        //  1. If Stripe is configured AND the target plan has a price ID,
        //     redirect to Stripe Checkout. Cashier returns a Stripe URL we
        //     redirect the user to; on success/cancel Stripe sends them back.
        //  2. Otherwise (dev mode, no Stripe keys), just flip the plan column
        //     directly so the rest of the app reflects the new tier.
        $priceId = config("plans.plans.{$data['plan']}.stripe_price");
        if (config('cashier.key') && $priceId) {
            // Real Stripe checkout — opens a hosted Stripe page.
            $session = $tenant->newSubscription('default', $priceId)->checkout([
                'success_url' => route('billing.index') . '?stripe=success',
                'cancel_url'  => route('billing.index') . '?stripe=cancelled',
            ]);

            return redirect()->away($session->url);
        }

        // Dev/demo mode — flip the plan without payment. We log to the
        // activity stream without performedOn() because Tenant uses a string
        // primary key and Spatie's polymorphic subject_id is bigint.
        $oldPlan = $tenant->plan;
        $tenant->update(['plan' => $data['plan']]);

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['from' => $oldPlan, 'to' => $data['plan']])
            ->log("switched plan from {$oldPlan} to {$data['plan']} (dev mode)");

        return redirect()->route('billing.index')
            ->with('status', "Plan switched to {$data['plan']}. (Dev mode — no payment taken. Configure STRIPE_KEY to enable real billing.)");
    }

    public function portal(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('owner'), 403);

        if (! config('cashier.key') || ! tenant()->hasStripeId()) {
            return back()->with('status', 'Stripe billing portal is only available once a paid plan is active.');
        }

        return tenant()->redirectToBillingPortal(route('billing.index'));
    }
}
