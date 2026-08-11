<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels — tenant-scoped
|--------------------------------------------------------------------------
| Every channel name embeds the tenant id. We only authorize a subscriber
| if the current request's tenant matches the one in the channel name —
| this prevents a user from Acme listening on Beta's channels even if they
| somehow guess a channel name.
*/

Broadcast::channel('tenant.{tenantId}.activity', function ($user, $tenantId) {
    return tenant('id') === $tenantId && $user !== null;
});

Broadcast::channel('tenant.{tenantId}.team', function ($user, $tenantId) {
    return tenant('id') === $tenantId && $user !== null;
});

Broadcast::channel('tenant.{tenantId}.alerts', function ($user, $tenantId) {
    return tenant('id') === $tenantId && $user !== null;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/*
|--------------------------------------------------------------------------
| Presence channel — who's online in this workspace right now
|--------------------------------------------------------------------------
| Returning an array (instead of true/false) on a presence channel sends
| that array as the user's "presence info" to all other subscribers.
*/

Broadcast::channel('presence-tenant.{tenantId}', function ($user, $tenantId) {
    if (tenant('id') !== $tenantId) {
        return false;
    }

    return [
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
        'role'  => $user->roles->pluck('name')->first() ?? 'member',
    ];
});
