<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DispatchWebhookDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->with('endpoint')->findOrFail($this->deliveryId);
        $endpoint = $delivery->endpoint;
        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, $endpoint->secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'ProfitLens-Webhooks/1.0',
                'Content-Type' => 'application/json',
                'X-ProfitLens-Event' => $delivery->event,
                'X-ProfitLens-Signature' => 'sha256=' . $signature,
            ])
            ->withBody($body, 'application/json')
            ->post($endpoint->url);

        $delivery->update([
            'status_code' => $response->status(),
            'response_body' => str($response->body())->limit(2000)->toString(),
            'delivered_at' => now(),
        ]);

        if ($response->failed()) {
            $this->release(60);
        }
    }
}
