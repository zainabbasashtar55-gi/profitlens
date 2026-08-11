<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseLogged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;

    public function __construct(public Expense $expense, public User $actor)
    {
        $this->tenantId = tenant('id');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.activity")];
    }

    public function broadcastAs(): string
    {
        return 'expense.logged';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->expense->id,
            'amount'      => (float) $this->expense->amount,
            'description' => $this->expense->description,
            'vendor'      => $this->expense->vendor,
            'category'    => $this->expense->category?->name,
            'actor'       => $this->actor->name,
            'actor_id'    => $this->actor->id,
            'at'          => now()->toIso8601String(),
        ];
    }
}
