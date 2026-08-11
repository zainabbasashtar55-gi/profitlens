<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
    ],

    'discord' => [
        'public_key' => env('DISCORD_PUBLIC_KEY'),
    ],

    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET'),
        'environment' => env('PLAID_ENV', 'sandbox'),
        'products' => array_filter(explode(',', env('PLAID_PRODUCTS', 'transactions'))),
        'country_codes' => array_filter(explode(',', env('PLAID_COUNTRY_CODES', 'US'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) — powers ProfitLens intelligence features
    |--------------------------------------------------------------------------
    | OCR receipt extraction, smart expense categorization, anomaly narratives
    | and the "ask your numbers" chat all route through the Claude API. Leave
    | ANTHROPIC_API_KEY empty to run on the built-in rule-based fallbacks.
    |
    | Model split: a cheap/fast model handles the high-volume work (OCR +
    | categorization) and the most capable model handles analysis + chat.
    */
    'anthropic' => [
        'key'      => env('ANTHROPIC_API_KEY'),
        'version'  => env('ANTHROPIC_VERSION', '2023-06-01'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'models'   => [
            'vision'     => env('ANTHROPIC_MODEL_VISION', 'claude-opus-4-8'),
            'categorize' => env('ANTHROPIC_MODEL_CATEGORIZE', 'claude-haiku-4-5'),
            'chat'       => env('ANTHROPIC_MODEL_CHAT', 'claude-opus-4-8'),
        ],
    ],

];
