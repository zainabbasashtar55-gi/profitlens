<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BigSaleAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Sale $sale, public User $recipient)
    {
    }

    public function broadcastOn(): array
    {
        // Personal channel — only this owner sees the celebration toast.
        return [new PrivateChannel("user.{$this->recipient->id}")];
    }

    public function broadcastAs(): string
    {
        return 'sale.big';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->sale->id,
            'revenue'  => (float) $this->sale->total_revenue,
            'profit'   => (float) $this->sale->total_profit,
            'customer' => $this->sale->customer?->name ?? 'walk-in',
            'message'  => '🎉 Big sale! $' . number_format((float) $this->sale->total_revenue, 2)
                          . ' — your biggest this month.',
        ];
    }
}
