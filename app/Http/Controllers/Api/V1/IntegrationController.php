<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'connections' => IntegrationConnection::query()
                ->latest()
                ->get(['id', 'provider', 'name', 'external_id', 'settings', 'last_synced_at', 'created_at']),
            'webhook_endpoints' => WebhookEndpoint::query()
                ->latest()
                ->get(['id', 'name', 'url', 'events', 'active', 'created_at']),
            'available' => [
                'plaid',
                'webhooks',
                'zapier',
                'browser_extension',
                'slack',
                'discord',
                'google_sheets',
            ],
        ]);
    }
}
