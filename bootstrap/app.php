<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

if (isset($_ENV['APP_STORAGE']) || getenv('APP_STORAGE')) {
    $app->useStoragePath($_ENV['APP_STORAGE'] ?? getenv('APP_STORAGE'));
}

return $app;
