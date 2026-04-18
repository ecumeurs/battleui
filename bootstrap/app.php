<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
// use Throwable;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        /** @spec-link [[api_laravel_health_check]] */
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\StandardEnvelope::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SanctumTokenRenewal::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*') || $request->is('v1/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        // catch all exception
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson()) {
                // 1. Resolve Request ID according to [[api_request_id]]
                $requestId = $request->input('request_id') 
                    ?? $request->header('X-Request-ID') 
                    ?? (string) Str::uuid7();
                
                $refId = substr($requestId, 0, 8);
                $now = now()->toIso8601ZuluString(); // YYYY-MM-DDTHH:MM:SSZ
                $endpoint = $request->getPathInfo();
                
                // 2. Log following [[rule_tracing_logging]]
                // [{YYYY-MM-DDTHH:MM:SSZ}] [{ref_id}] [{ENDPOINT_OR_HANDLER}] {Message}
                Log::error("[{$now}] [{$refId}] [{$endpoint}] {$e->getMessage()}", [
                    'exception' => $e,
                    'request_id' => $requestId
                ]);

                // 3. Determine Status Code
                $statusCode = 500;
                if ($e instanceof HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $statusCode = 401;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                }

                // 4. Secure Message: no stack trace, simplified for 500s in non-debug
                $message = $e->getMessage();
                $meta = (object) [];

                if ($statusCode === 422 && $e instanceof \Illuminate\Validation\ValidationException) {
                    $message = 'Validation failed';
                    $meta = ['errors' => $e->errors()];
                } elseif ($statusCode >= 500 && !config('app.debug')) {
                    $message = 'Internal Server Error';
                }

                if (config('app.debug')) {
                    $message = '-- DEBUG MODE -- ' . $message;
                }

                // 5. Return JSON Envelope [[api_standard_envelope]]
                return response()->json([
                    'request_id' => $requestId,
                    'message'    => $message,
                    'success'    => false,
                    'data'       => (object) [],
                    'meta'       => $meta,
                ], $statusCode);
            }
        });
    })
    ->create();
