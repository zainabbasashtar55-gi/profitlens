<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebhookEndpointController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => WebhookEndpoint::query()->latest()->get()->map(fn (WebhookEndpoint $endpoint) => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
                'url' => $endpoint->url,
                'events' => $endpoint->events,
                'active' => $endpoint->active,
                'created_at' => $endpoint->created_at?->toIso8601String(),
            ]),
            'available_events' => WebhookEndpoint::EVENTS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $data = $this->validatePayload($request);
        $endpoint = WebhookEndpoint::create($data + ['secret' => WebhookEndpoint::makeSecret()]);

        return response()->json([
            'data' => $endpoint,
            'secret' => $endpoint->secret,
        ], 201);
    }

    public function update(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $webhookEndpoint->update($this->validatePayload($request));

        return response()->json(['data' => $webhookEndpoint]);
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $webhookEndpoint->delete();

        return response()->json(null, 204);
    }

    public function rotateSecret(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $webhookEndpoint->update(['secret' => WebhookEndpoint::makeSecret()]);

        return response()->json(['secret' => $webhookEndpoint->secret]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', Rule::in(WebhookEndpoint::EVENTS)],
            'active' => ['sometimes', 'boolean'],
        ]);
    }
}
