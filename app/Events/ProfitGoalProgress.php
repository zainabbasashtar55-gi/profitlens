<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts updated profit-goal progress to every active dashboard so the
 * progress bar advances live as sales come in. Rides the existing tenant
 * activity channel.
 */
class ProfitGoalProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string,mixed>  $progress
     */
    public function __construct(
        public string $tenantId,
        public array $progress,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.activity")];
    }

    public function broadcastAs(): string
    {
        return 'goal.progress'
    }

    public function broadcastWith(): array
    {
        return $this->progress;
    }
}
