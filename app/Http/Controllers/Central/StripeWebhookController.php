<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Models\Tenant;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

/**
 * Extends Cashier's webhook controller so we can sync the tenant's `plan`
 * column whenever Stripe tells us a subscription was created, updated, or
 * cancelled. Cashier handles the subscription/subscription_items tables
 * automatically — we just keep `tenants.plan` in sync for fast UI lookups.
 */
class StripeWebhookController extends CashierController
{
    /**
     * Stripe fires this when a subscription is created, renewed, paused,
     * or its price changes.
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        $tenant = $stripeCustomerId ? Tenant::where('stripe_id', $stripeCustomerId)->first() : null;

        if ($tenant) {
            $priceId  = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
            $status   = $payload['data']['object']['status'] ?? null;
            $newPlan  = $this->resolvePlanFromPriceId($priceId, $status);

            if ($newPlan && $tenant->plan !== $newPlan) {
                $tenant->update(['plan' => $newPlan]);
            }
        }

        return $response;
    }

    /**
     * Stripe fires this when a subscription is fully cancelled.
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        $tenant = $stripeCustomerId ? Tenant::where('stripe_id', $stripeCustomerId)->first() : null;

        if ($tenant && $tenant->plan !== 'free') {
            $tenant->update(['plan' => 'free']);
        }

        return $response;
    }

    private function resolvePlanFromPriceId(?string $priceId, ?string $status): ?string
    {
        if (! in_array($status, ['active', 'trialing'], true) || ! $priceId) {
            return null;
        }

        foreach (config('plans.plans') as $slug => $plan) {
            if (($plan['stripe_price'] ?? null) === $priceId) {
                return $slug;
            }
        }

        return null;
    }
}
