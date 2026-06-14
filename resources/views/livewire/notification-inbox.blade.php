<div style="max-width:720px;margin:0 auto;padding:40px 20px;">
    <div class="between" style="margin-bottom:28px;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div class="col" style="gap:4px;">
            <span class="kicker">NOTIFICATIONS</span>
            <h1 class="display" style="font-size:32px;">{{ $registration->name }}</h1>
            <span class="mono dim" style="font-size:11px;">{{ $registration->entry_code }}</span>
        </div>
        <div class="flex gap-s" style="align-items:center;">
            @if($unreadCount > 0)
                <span class="badge badge-red">{{ $unreadCount }} UNREAD</span>
                <button wire:click="markAllRead" class="btn btn-sm btn-ghost">Mark all read</button>
            @else
                <span class="badge badge-lime">ALL CAUGHT UP</span>
            @endif
        </div>
    </div>

    @forelse($notifications as $notif)
        <div class="panel" style="padding:16px 18px;margin-bottom:10px;{{ $notif->read_at ? 'opacity:.65;' : '' }}">
            <div class="between" style="margin-bottom:6px;">
                <div class="flex gap-s" style="align-items:center;">
                    @unless($notif->read_at)
                        <span style="width:8px;height:8px;background:var(--lime);border-radius:999px;flex-shrink:0;display:block;"></span>
                    @endunless
                    <span class="label" style="font-size:14px;">{{ $notif->title }}</span>
                </div>
                <span class="mono dim" style="font-size:10px;">{{ $notif->created_at->diffForHumans() }}</span>
            </div>
            <p style="font-size:13px;color:var(--ink-dim);line-height:1.55;margin:0 0 10px;">{{ $notif->body }}</p>
            @unless($notif->read_at)
                <button wire:click="markRead({{ $notif->id }})" class="mono dim" style="font-size:11px;text-decoration:underline;">Mark as read</button>
            @endunless
        </div>
    @empty
        <div class="panel center col" style="padding:60px;gap:16px;text-align:center;">
            <span style="font-size:44px;">📭</span>
            <h3 class="display" style="font-size:24px;">No notifications yet</h3>
            <p class="dim">You'll be notified when your registration is reviewed, matches are assigned, and more.</p>
        </div>
    @endforelse

    <div style="margin-top:28px;text-align:center;">
        <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">← Back to Home</a>
    </div>
</div>
