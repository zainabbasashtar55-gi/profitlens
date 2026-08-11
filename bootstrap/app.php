<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('tenant.login'));

        // Track last_seen_at on every authenticated request (throttled per-user).
        $middleware->alias([
            'track.lastseen' => \App\Http\Middleware\TrackLastSeen::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\TrackLastSeen::class]);
        $middleware->api(append: [\App\Http\Middleware\TrackLastSeen::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
