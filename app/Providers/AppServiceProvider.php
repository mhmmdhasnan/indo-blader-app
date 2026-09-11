<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Cloudflare Tunnel terminates TLS and forwards plain HTTP to the
        // container, so Laravel otherwise thinks every request is insecure
        // and generates http:// asset/Livewire URLs — which browsers block
        // as mixed content on an https:// page. Force https whenever APP_URL
        // itself is https (i.e. in production), leaving local/LAN http access
        // untouched.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
