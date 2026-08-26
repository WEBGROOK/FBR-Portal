<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production or behind proxies (Vercel, Cloudflare, AWS)
        if (
            $this->app->environment('production') ||
            request()->header('x-forwarded-proto') === 'https' ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ) {
            URL::forceScheme('https');
        }
    }
}
