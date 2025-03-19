<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetUserLocale;
use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\UserMiddleware;

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
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // x-language header middleware
        $middleware->append([
            SetUserLocale::class,
            UserMiddleware::class
        ]);

        // Make sure the built-in JWT middleware is removed if it's being registered elsewhere
        $middleware->remove(\Tymon\JWTAuth\Http\Middleware\Authenticate::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Register your custom exception handler for API routes
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return app(ApiExceptionHandler::class)->render($request, $e);
            }
        });
    })->create();
