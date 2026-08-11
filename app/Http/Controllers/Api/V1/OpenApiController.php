<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'ProfitLens API',
                'version' => '1.0.0',
                'description' => 'Tenant-scoped ProfitLens API for sales, expenses, analytics, and integrations.',
            ],
            'servers' => [
                ['url' => url('/api/v1')],
            ],
            'security' => [['sanctum' => []]],
            'paths' => [
                '/auth/login' => ['post' => ['summary' => 'Create an API token']],
                '/auth/me' => ['get' => ['summary' => 'Return the current API user']],
                '/customers' => ['get' => ['summary' => 'List customers'], 'post' => ['summary' => 'Create customer']],
                '/products' => ['get' => ['summary' => 'List products'], 'post' => ['summary' => 'Create product']],
                '/sales' => ['get' => ['summary' => 'List sales'], 'post' => ['summary' => 'Create sale']],
                '/expenses' => ['get' => ['summary' => 'List expenses'], 'post' => ['summary' => 'Create expense']],
                '/analytics/dashboard' => ['get' => ['summary' => 'Dashboard metrics']],
                '/analytics/profit-loss' => ['get' => ['summary' => 'Profit and loss report']],
                '/webhook-endpoints' => ['get' => ['summary' => 'List webhook endpoints'], 'post' => ['summary' => 'Create webhook endpoint']],
                '/integrations/plaid/link-token' => ['post' => ['summary' => 'Create Plaid Link token']],
                '/integrations/plaid/exchange-token' => ['post' => ['summary' => 'Exchange Plaid public token']],
                '/integrations/plaid/import' => ['post' => ['summary' => 'Import Plaid transactions']],
                '/integrations/google-sheets/export' => ['get' => ['summary' => 'Export rows for Google Sheets']],
                '/integrations/google-sheets/import' => ['post' => ['summary' => 'Import rows from Google Sheets']],
                '/integrations/zapier/triggers/sales' => ['get' => ['summary' => 'Zapier sale trigger feed']],
                '/integrations/zapier/triggers/expenses' => ['get' => ['summary' => 'Zapier expense trigger feed']],
                '/integrations/browser-extension/expense' => ['post' => ['summary' => 'Create browser-captured expense']],
                '/integrations/slack/command' => ['post' => ['summary' => 'Slack slash command handler']],
                '/integrations/discord/command' => ['post' => ['summary' => 'Discord command handler']],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
            'x-profitlens' => [
                'rate_limits' => [
                    'free' => '60/min',
                    'pro' => '1000/min',
                    'enterprise' => '5000/min',
                ],
                'webhook_events' => \App\Models\WebhookEndpoint::EVENTS,
                'scribe' => [
                    'command' => 'php artisan scribe:generate',
                    'status' => class_exists(\Knuckles\Scribe\Scribe::class) ? 'installed' : 'install knuckleswtf/scribe to publish a full docs site',
                ],
            ],
        ]);
    }
}
