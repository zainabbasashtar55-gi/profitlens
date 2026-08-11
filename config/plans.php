<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Subscription plans
|--------------------------------------------------------------------------
|
| Every workspace ("tenant") is on one of these plans. The `slug` is what
| we store on `tenants.plan`. Limits are enforced server-side at every
| insert path (invitations, sales, products, expenses).
|
| `stripe_price` is the Stripe Price ID — set these in .env for prod.
| In dev (no STRIPE_KEY set), plan switching happens directly without
| charging anyone.
|
*/

return [

    'plans' => [

        'free' => [
            'name'         => 'Free',
            'price_cents'  => 0,
            'stripe_price' => null,
            'highlight'    => false,
            'limits' => [
                'users'           => 3,
                'sales_per_month' => 100,
                'products'        => 25,
                'storage_mb'      => 50,
                'api_requests_per_minute' => 60,
            ],
            'features' => [
                '1 workspace',
                '3 users',
                '100 sales per month',
                'Community support',
            ],
        ],

        'pro' => [
            'name'         => 'Pro',
            'price_cents'  => 2900,
            'stripe_price' => env('STRIPE_PRICE_PRO'),
            'highlight'    => true,
            'limits' => [
                'users'           => 25,
                'sales_per_month' => 10_000,
                'products'        => 1_000,
                'storage_mb'      => 2_048,
                'api_requests_per_minute' => 1_000,
            ],
            'features' => [
                'Up to 25 users',
                '10,000 sales per month',
                'Custom domains',
                'Priority email support',
                'API rate limit: 1,000/min',
            ],
        ],

        'enterprise' => [
            'name'         => 'Enterprise',
            'price_cents'  => 19900,
            'stripe_price' => env('STRIPE_PRICE_ENTERPRISE'),
            'highlight'    => false,
            'limits' => [
                'users'           => PHP_INT_MAX,
                'sales_per_month' => PHP_INT_MAX,
                'products'        => PHP_INT_MAX,
                'storage_mb'      => 51_200,
                'api_requests_per_minute' => 5_000,
            ],
            'features' => [
                'Unlimited users',
                'Unlimited sales',
                'SSO / SAML',
                'SLA + dedicated CSM',
                'White-label option',
            ],
        ],

    ],

];
