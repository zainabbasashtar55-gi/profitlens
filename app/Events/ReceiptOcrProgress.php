<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Streams OCR progress for a receipt back to the *uploading* user only.
 * Status is one of: queued, processing, done, failed.
 */
class ReceiptOcrProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $expenseId,
        public string $jobId,
        public int $percent,
        public string $status,
        public ?string $message = null,
        public ?array $extracted = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'ocr.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id'     => $this->jobId,
            'expense_id' => $this->expenseId,
            'percent'    => $this->percent,
            'status'     => $this->status,
            'message'    => $this->message,
            'extracted'  => $this->extracted,
        ];
    }
}
