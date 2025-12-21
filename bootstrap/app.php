<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        channels: __DIR__ . '/../routes/channels.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function (Request $request) {
            $apiPaths = ['me', 'logout', 'posts', 'friendships', 'notifications', 'conversations', 'profile'];
            $path = $request->path();
            $isApiPath = collect($apiPaths)->contains(fn($apiPath) => str_starts_with($path, $apiPath));

            if ($request->expectsJson() || $isApiPath) {
                return null;
            }

            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
