<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventDivision;
use App\Models\JudgeScore;
use App\Observers\EventDivisionObserver;
use App\Observers\EventObserver;
use App\Observers\JudgeScoreObserver;
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
        // Push a LiveScoreUpdated broadcast whenever the state that drives the
        // public /live display actually changes, instead of every viewer
        // polling the server on a timer regardless of whether anything moved.
        Event::observe(EventObserver::class);
        EventDivision::observe(EventDivisionObserver::class);
        JudgeScore::observe(JudgeScoreObserver::class);

        // Scheme detection (http vs https) behind Cloudflare Tunnel is handled
        // by trusting the proxy's X-Forwarded-Proto header — see
        // bootstrap/app.php's trustProxies() call. Don't force the scheme
        // here: forcing it globally would also apply to direct LAN access
        // (which has no TLS), breaking signed URLs (Livewire uploads, etc.)
        // there since the signature is checked against the actual request.
        // URL::forceScheme('https');
    }
}
