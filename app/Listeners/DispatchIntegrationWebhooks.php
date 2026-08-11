<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BigSaleAlert;
use App\Events\ExpenseLogged;
use App\Events\SaleRecorded;
use App\Jobs\DispatchWebhookDelivery;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

class DispatchIntegrationWebhooks
{
    public function handle(BigSaleAlert|ExpenseLogged|SaleRecorded $event): void
    {
        $name = $event->broadcastAs();
        $payload = [
            'id' => (string) str()->uuid(),
            'event' => $name,
            'tenant_id' => tenant('id'),
            'created_at' => now()->toIso8601String(),
            'data' => $event->broadcastWith(),
        ];

        WebhookEndpoint::query()
            ->where('active', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint) => in_array($name, $endpoint->events ?? [], true))
            ->each(function (WebhookEndpoint $endpoint) use ($name, $payload) {
                $delivery = WebhookDelivery::create([
                    'webhook_endpoint_id' => $endpoint->id,
                    'event' => $name,
                    'payload' => $payload,
                ]);

                DispatchWebhookDelivery::dispatch($delivery->id);
            });
    }
}
