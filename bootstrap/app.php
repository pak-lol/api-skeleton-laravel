<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetUserLocale;
use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\UserMiddleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Add Sanctum middleware to api group
        $middleware->prependToGroup('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // x-language header middleware
        $middleware->append([
            SetUserLocale::class,
            UserMiddleware::class
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Register your custom exception handler for API routes
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return app(ApiExceptionHandler::class)->render($request, $e);
            }
        });

        Integration::handles($exceptions);
    })->create();
