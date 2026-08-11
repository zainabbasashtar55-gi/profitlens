<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;

    public function __construct(public Sale $sale, public User $actor)
    {
        $this->tenantId = tenant('id');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.activity"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sale.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->sale->id,
            'revenue'   => (float) $this->sale->total_revenue,
            'profit'    => (float) $this->sale->total_profit,
            'customer'  => $this->sale->customer?->name ?? 'walk-in',
            'status'    => $this->sale->status,
            'actor'     => $this->actor->name,
            'actor_id'  => $this->actor->id,
            'at'        => now()->toIso8601String(),
        ];
    }
}
