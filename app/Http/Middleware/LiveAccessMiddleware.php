<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate untuk /live: role mana yang boleh membukanya diatur Admin lewat
 * Setting 'live_access_roles' (dashboard admin → Akses Live).
 */
class LiveAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = Setting::liveAccessRoles();

        if (! auth()->check()) {
            return in_array('guest', $allowed) ? $next($request) : redirect()->route('login');
        }

        abort_unless(in_array(auth()->user()->role, $allowed), 403);

        return $next($request);
    }
}
