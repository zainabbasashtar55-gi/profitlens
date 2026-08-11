<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ReceiptOcrProgress;
use App\Models\Expense;
use App\Services\Ai\ClaudeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Receipt OCR pipeline — backed by Claude vision.
 *
 * Reads the uploaded receipt (image or PDF) off the tenant's receipts disk,
 * sends it to the Claude Messages API, and asks for the vendor / amount / date
 * as structured JSON. Progress is streamed over the uploader's private
 * user.{id} channel so the form / dashboard show a live progress bar.
 *
 * The broadcast contract (queued → processing → done/failed, with an
 * `extracted` payload on done) is unchanged from the original simulated job —
 * the front-end binds to that, not to the provider. When no ANTHROPIC_API_KEY
 * is configured we fall back to echoing the values the user already typed so
 * the feature still completes gracefully.
 */
class ProcessReceiptOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $expenseId,
        public int $userId,
        public string $jobId,
    ) {}

    public function handle(ClaudeClient $claude): void
    {
        $expense = Expense::find($this->expenseId);
        if (! $expense) {
            $this->broadcast(0, 'failed', 'Expense not found.');

            return;
        }

        $this->broadcast(15, 'processing', 'Reading receipt…');

        $disk = config('filesystems.receipts_disk');
        if (! $expense->receipt_path || ! Storage::disk($disk)->exists($expense->receipt_path)) {
            $this->broadcast(100, 'done', 'Receipt saved — no file to scan.', $this->fallbackExtraction($expense));

            return;
        }

        // No API key → skip the model call, complete with what we have.
        if (! $claude->enabled()) {
            $this->broadcast(100, 'done', 'Receipt saved (AI OCR disabled).', $this->fallbackExtraction($expense));

            return;
        }

        $this->broadcast(55, 'processing', 'Extracting vendor, date, total with AI…');

        $extracted = $this->extractWithClaude($claude, $expense, $disk);

        if ($extracted === null) {
            $this->broadcast(100, 'done', 'Could not read receipt — review manually.', $this->fallbackExtraction($expense));

            return;
        }

        $this->broadcast(100, 'done', 'Done — review the suggestions below.', $extracted);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessReceiptOcr failed', ['error' => $e->getMessage(), 'expense' => $this->expenseId]);
        $this->broadcast(0, 'failed', 'OCR failed — receipt saved without auto-fill.');
    }

    /**
     * @return array<string,mixed>|null
     */
    private function extractWithClaude(ClaudeClient $claude, Expense $expense, string $disk): ?array
    {
        $bytes = Storage::disk($disk)->get($expense->receipt_path);
        $mediaType = $this->mediaType($expense->receipt_path, $disk);

        // PDFs go as a `document` block, images as an `image` block.
        $fileBlock = str_contains($mediaType, 'pdf')
            ? [
                'type' => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($bytes)],
            ]
            : $claude->imageBlock($bytes, $mediaType);

        $system = <<<'PROMPT'
        You are a receipt OCR engine. Extract the merchant/vendor name, the grand
        total paid, the transaction date, and the currency from the receipt image.
        Respond with ONLY a single JSON object — no prose, no code fences:
        {"vendor": string, "amount": number, "expense_date": "YYYY-MM-DD", "currency": "ISO 4217 code", "confidence": number between 0 and 1}
        Use the grand total (after tax), not a subtotal. If a field is illegible,
        use null for that field and lower the confidence. Do not invent values.
        PROMPT;

        $result = $claude->completeJson(
            model: $claude->model('vision'),
            messages: [[
                'role' => 'user',
                'content' => [
                    $fileBlock,
                    ['type' => 'text', 'text' => 'Extract the receipt fields as JSON.'],
                ],
            ]],
            system: $system,
            maxTokens: 512,
            timeout: 90,
        );

        if ($result === null) {
            return null;
        }

        return [
            'vendor' => $this->cleanString($result['vendor'] ?? null) ?: ($expense->vendor ?: null),
            'amount' => $this->cleanAmount($result['amount'] ?? null) ?? (float) $expense->amount,
            'expense_date' => $this->cleanDate($result['expense_date'] ?? null)
                ?? (optional($expense->expense_date)->toDateString() ?: now()->toDateString()),
            'currency' => $this->cleanString($result['currency'] ?? null) ?: 'USD',
            'confidence' => $this->cleanConfidence($result['confidence'] ?? null),
        ];
    }

    private function mediaType(string $path, string $disk): string
    {
        try {
            $mime = Storage::disk($disk)->mimeType($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        } catch (Throwable) {
            // fall through to extension sniffing
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'image/jpeg',
        };
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function cleanAmount(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }
        if (is_string($value)) {
            $stripped = preg_replace('/[^0-9.\-]/', '', $value);
            if ($stripped !== '' && is_numeric($stripped)) {
                return round((float) $stripped, 2);
            }
        }

        return null;
    }

    private function cleanDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function cleanConfidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.5;
        }

        return max(0.0, min(1.0, round((float) $value, 2)));
    }

    /**
     * @return array<string,mixed>
     */
    private function fallbackExtraction(Expense $expense): array
    {
        return [
            'vendor' => $expense->vendor ?: null,
            'amount' => (float) $expense->amount,
            'expense_date' => optional($expense->expense_date)->toDateString() ?: now()->toDateString(),
            'currency' => 'USD',
            'confidence' => 0.0,
        ];
    }

    private function broadcast(int $percent, string $status, string $message, ?array $extracted = null): void
    {
        ReceiptOcrProgress::dispatch(
            $this->userId,
            $this->expenseId,
            $this->jobId,
            $percent,
            $status,
            $message,
            $extracted,
        );
    }
}
