<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class SimulatorController extends Controller
{
    private const PAGES = [
        '/admin'          => 'Admin',
        '/judge'          => 'Judge / Operator',
        '/live'           => 'Live Scoring',
        '/bracket'        => 'Bracket',
        '/rider'          => 'Rider',
        '/events'         => 'Events',
        '/'               => 'Home',
    ];

    public function index()
    {
        $users = User::orderBy('role')->orderBy('name')->get(['id', 'name', 'username', 'role']);

        $first = fn (string $role, int $n = 1) => $users->where('role', $role)->take($n)->values();

        $defaults = collect()
            ->merge($first('operator')->map(fn ($u) => ['user' => $u->id, 'page' => '/judge']))
            ->merge($first('head_judge')->map(fn ($u) => ['user' => $u->id, 'page' => '/judge']))
            ->merge($first('judge', 3)->map(fn ($u) => ['user' => $u->id, 'page' => '/judge']))
            ->push(['user' => 0, 'page' => '/live'])
            ->merge($first('admin')->map(fn ($u) => ['user' => $u->id, 'page' => '/admin']))
            ->values();

        return view('simulator.index', [
            'users'    => $users,
            'pages'    => self::PAGES,
            'defaults' => $defaults,
        ]);
    }

    /** Membuat URL bertanda tangan untuk memasukkan sebuah slot sebagai user tertentu. */
    public function link(Request $request)
    {
        $data = $request->validate([
            'slot' => ['required', 'regex:/^[a-z0-9_]{1,12}$/'],
            'user' => ['required', 'integer', 'min:0'],
            'page' => ['required', 'string', 'starts_with:/'],
        ]);

        if ((int) $data['user'] === 0) {
            return ['url' => url($data['page']) . '?sim=' . $data['slot']];
        }

        return ['url' => URL::temporarySignedRoute('simulator.enter', now()->addMinutes(2), [
            'sim'  => $data['slot'],
            'user' => $data['user'],
            'to'   => $data['page'],
        ])];
    }

    /** Dipanggil di dalam iframe: login sebagai user pilihan di sesi slot ini. */
    public function enter(Request $request)
    {
        $to = (string) $request->query('to', '/');
        abort_unless(str_starts_with($to, '/') && ! str_starts_with($to, '//'), 400);

        Auth::loginUsingId((int) $request->query('user'));
        $request->session()->regenerate();

        return redirect($to);
    }
}
