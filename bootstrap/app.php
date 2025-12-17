<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Exclude broadcasting auth from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'broadcasting/auth',
        ]);

        // Register custom route middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'profile.completed' => \App\Http\Middleware\EnsureProfileCompleted::class,
        ]);

        // Trust ngrok and other proxies so HTTPS is correctly detected behind them
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle Inertia requests - return JSON instead of trying to render exception views
        $exceptions->respond(function (\Illuminate\Http\Request $request, \Throwable $e) {
            // For Inertia requests or API requests, return JSON response
            if ($request->expectsJson() || $request->header('X-Inertia') || $request->is('api/*')) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                // Handle validation exceptions
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422);
                }
                
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => config('app.debug') ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                    ] : null,
                ], $statusCode);
            }
        });
    })->create();
