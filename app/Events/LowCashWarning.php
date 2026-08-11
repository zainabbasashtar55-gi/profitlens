<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowCashWarning implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;

    public function __construct(
        public float $netProfit,
        public float $threshold,
        public string $period = 'this month',
    ) {
        $this->tenantId = tenant('id');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.alerts")];
    }

    public function broadcastAs(): string
    {
        return 'cashflow.warning';
    }

    public function broadcastWith(): array
    {
        return [
            'net_profit' => $this->netProfit,
            'threshold'  => $this->threshold,
            'period'     => $this->period,
            'message'    => "⚠️ Net profit for {$this->period} dropped to "
                            . '$' . number_format($this->netProfit, 2)
                            . ' — below your $' . number_format($this->threshold, 0) . ' threshold.',
        ];
    }
}
