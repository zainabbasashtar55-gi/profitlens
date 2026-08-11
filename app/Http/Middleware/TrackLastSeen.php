<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * Update users.last_seen_at + last_seen_ip on every authenticated tenant
     * request, but skip if the user was already seen in the last minute.
     * We check the column directly instead of using Cache (the database
     * cache driver inside a tenant context doesn't support tagging).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && tenant() && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute()))) {
            // forceFill + saveQuietly so we don't fire model events
            // (we don't want this to spam the activity log every minute).
            $user->forceFill([
                'last_seen_at' => now(),
                'last_seen_ip' => $request->ip(),
            ])->saveQuietly();
        }

        return $next($request);
    }
}
