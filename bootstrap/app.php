<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
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
            'superadmin' => \App\Http\Middleware\EnsureSuperadmin::class,
            'registration.geo' => \App\Http\Middleware\EnsureRegistrationGeoVerified::class,
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
        // Laravel passes ($response, $e, $request) to respond callbacks — see Handler::finalizeRenderedResponse().
        $exceptions->respond(function (SymfonyResponse $response, \Throwable $e, Request $request): SymfonyResponse {
            // Inertia visits must receive redirect + session errors, not a bare JSON 422 (breaks useForm error handling).
            if ($e instanceof ValidationException && $request->header('X-Inertia')) {
                return $response;
            }

            if ($e instanceof PostTooLargeException && $request->header('X-Inertia')) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'photo' => 'The upload is too large for the server (over PHP limits). Use a file up to 5 MB, or ask your host to increase post_max_size and upload_max_filesize.',
                    ]);
            }

            if (! $request->expectsJson() && ! $request->header('X-Inertia') && ! $request->is('api/*')) {
                return $response;
            }

            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
            ], $statusCode);
        });
    })->create();
