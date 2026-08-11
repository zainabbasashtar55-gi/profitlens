<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;

    public function __construct(public User $user, public string $role)
    {
        $this->tenantId = tenant('id');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.team")];
    }

    public function broadcastAs(): string
    {
        return 'invitation.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'id'    => $this->user->id,
            'name'  => $this->user->name,
            'email' => $this->user->email,
            'role'  => $this->role,
            'at'    => now()->toIso8601String(),
        ];
    }
}
