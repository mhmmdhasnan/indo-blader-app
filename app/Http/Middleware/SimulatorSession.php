<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alat bantu simulasi (hanya aktif jika SIMULATOR_ENABLED=true).
 *
 * Tiap panel di halaman /simulator memakai "slot" (?sim=p1, p2, ...) dan tiap
 * slot mendapat cookie sesi sendiri, sehingga satu browser bisa login sebagai
 * banyak user sekaligus. Slot dibaca dari query ?sim= atau dari Referer
 * (untuk request Livewire/fetch yang URL-nya tidak membawa ?sim=).
 *
 * Middleware ini harus berjalan SEBELUM StartSession.
 */
class SimulatorSession
{
    public static function slot(Request $request): ?string
    {
        if (! config('app.simulator')) {
            return null;
        }

        $slot = $request->query('sim');

        if (! is_string($slot) || $slot === '') {
            $query = parse_url((string) $request->headers->get('referer'), PHP_URL_QUERY);
            if ($query) {
                parse_str($query, $params);
                $slot = $params['sim'] ?? null;
            }
        }

        return is_string($slot) && preg_match('/^[a-z0-9_]{1,12}$/', $slot) ? $slot : null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slot = self::slot($request);

        if ($slot === null) {
            return $next($request);
        }

        config(['session.cookie' => config('session.cookie') . '_sim_' . $slot]);

        $response = $next($request);

        // Redirect tetap berada di slot yang sama.
        if ($response instanceof RedirectResponse) {
            $target = $response->getTargetUrl();
            if (str_starts_with($target, $request->getSchemeAndHttpHost()) && ! str_contains($target, 'sim=')) {
                $response->setTargetUrl($target . (str_contains($target, '?') ? '&' : '?') . 'sim=' . $slot);
            }

            return $response;
        }

        // Halaman HTML penuh: pastikan URL & link di dalamnya selalu membawa ?sim=.
        $type = (string) $response->headers->get('Content-Type');
        $body = $response->getContent();
        if (str_contains($type, 'text/html') && is_string($body) && str_contains($body, '</body>')) {
            $script = '<script>(function(s){function fix(){var u=new URL(location.href);if(u.searchParams.get("sim")!==s){u.searchParams.set("sim",s);history.replaceState(history.state,"",u)}}fix();document.addEventListener("livewire:navigated",fix);document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a[href]");if(!a)return;var u=new URL(a.href,location.href);if(u.origin===location.origin&&u.searchParams.get("sim")!==s){u.searchParams.set("sim",s);a.setAttribute("href",u.pathname+u.search+u.hash)}},true)})(' . json_encode($slot) . ');</script>';
            $response->setContent(substr_replace($body, $script, strrpos($body, '</body>'), 0));
        }

        return $response;
    }
}
