<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around the Anthropic Messages API (POST /v1/messages).
 *
 * We talk to the API over Laravel's HTTP client rather than pulling in the
 * Anthropic PHP SDK — the wire contract here is tiny and stable, it keeps the
 * call inside the existing queue/tenancy context with no extra dependency, and
 * it lets every feature degrade gracefully when no key is configured.
 *
 * If ANTHROPIC_API_KEY is unset, enabled() returns false and callers fall back
 * to their deterministic rule-based paths.
 */
class ClaudeClient
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function model(string $role): string
    {
        return config("services.anthropic.models.{$role}")
            ?? config('services.anthropic.models.chat')
            ?? 'claude-opus-4-8';
    }

    /**
     * Send a Messages API request and return the concatenated text content,
     * or null on any failure / when the API is not configured.
     *
     * @param  array<int,array<string,mixed>>  $messages  Anthropic message blocks
     */
    public function complete(
        string $model,
        array $messages,
        ?string $system = null,
        int $maxTokens = 1024,
        array $extra = [],
        int $timeout = 60,
    ): ?string {
        if (! $this->enabled()) {
            return null;
        }

        $payload = array_merge([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ], $extra);

        if ($system !== null) {
            $payload['system'] = $system;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => config('services.anthropic.version'),
                'content-type' => 'application/json',
            ])
                ->timeout($timeout)
                ->retry(2, 500, throw: false)
                ->post(rtrim((string) config('services.anthropic.base_url'), '/').'/v1/messages', $payload);

            if ($response->failed()) {
                Log::warning('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->json('error.message') ?? $response->body(),
                ]);

                return null;
            }

            return $this->extractText($response->json('content') ?? []);
        } catch (Throwable $e) {
            Log::warning('Claude API request threw', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Convenience: ask for a JSON object back and decode it tolerantly.
     *
     * @return array<string,mixed>|null
     */
    public function completeJson(
        string $model,
        array $messages,
        ?string $system = null,
        int $maxTokens = 1024,
        array $extra = [],
        int $timeout = 60,
    ): ?array {
        $text = $this->complete($model, $messages, $system, $maxTokens, $extra, $timeout);

        return $text === null ? null : $this->decodeJson($text);
    }

    /**
     * Build an image content block from raw bytes for a vision request.
     *
     * @return array<string,mixed>
     */
    public function imageBlock(string $bytes, string $mediaType): array
    {
        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $mediaType,
                'data' => base64_encode($bytes),
            ],
        ];
    }

    /**
     * Concatenate the text from all text-type content blocks.
     *
     * @param  array<int,array<string,mixed>>  $content
     */
    private function extractText(array $content): string
    {
        $parts = [];
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'text' && isset($block['text'])) {
                $parts[] = $block['text'];
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * Pull the first balanced JSON object out of a model response and decode it.
     * Tolerates code fences and stray prose around the object.
     *
     * @return array<string,mixed>|null
     */
    private function decodeJson(string $text): ?array
    {
        $text = trim($text);

        // Strip ```json fences if present.
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text) ?? $text;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $candidate = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }
}
