<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust reverse proxies (Vercel, Cloudflare, AWS)
        $middleware->trustProxies(at: '*');

        // Global security response headers
        $middleware->append(function (Request $request, Closure $next) {
            $response = $next($request);

            if (method_exists($response, 'header')) {
                $response->header('X-Content-Type-Options', 'nosniff');
                $response->header('X-Frame-Options', 'SAMEORIGIN');
                $response->header('X-XSS-Protection', '1; mode=block');
                $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
                if ($request->isSecure() || $request->header('x-forwarded-proto') === 'https') {
                    $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
                }
            }

            return $response;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

if (isset($_ENV['APP_STORAGE']) || getenv('APP_STORAGE')) {
    $app->useStoragePath($_ENV['APP_STORAGE'] ?? getenv('APP_STORAGE'));
}

return $app;
