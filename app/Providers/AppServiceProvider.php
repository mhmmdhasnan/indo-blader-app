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
        // Scheme detection (http vs https) behind Cloudflare Tunnel is handled
        // by trusting the proxy's X-Forwarded-Proto header — see
        // bootstrap/app.php's trustProxies() call. Don't force the scheme
        // here: forcing it globally would also apply to direct LAN access
        // (which has no TLS), breaking signed URLs (Livewire uploads, etc.)
        // there since the signature is checked against the actual request.
        // URL::forceScheme('https');
    }
}
