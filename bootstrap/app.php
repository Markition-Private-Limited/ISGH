<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/membership/webhook',
        ]);
        $middleware->alias([
            'admin.token'          => \App\Http\Middleware\AdminToken::class,
            'portal.auth'          => \App\Http\Middleware\PortalAuth::class,
            'active.user'          => \App\Http\Middleware\CheckActiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $_, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return redirect()->route('portal.login')
                    ->with('error', 'Please sign in to access the staff portal.');
            }
        });
    })->create();
