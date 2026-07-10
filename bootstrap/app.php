<?php

use App\Http\Middleware\EnsureSessionIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['session.active', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'session.active' => EnsureSessionIsActive::class,
        ]);

        // Laravel re-sorts route middleware at dispatch time using its own
        // priority list, regardless of the order they're listed in on the
        // route itself. That list references the *interface* Authenticate
        // implements (AuthenticatesRequests), not the concrete class — so
        // the "before" target below must match that, or the entry is never
        // found and this call silently does nothing. Without it, auth:sanctum
        // runs (and stamps last_used_at) before our idle check ever sees
        // the token's previous activity.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: EnsureSessionIsActive::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
