<div style="max-width:900px;margin:0 auto;padding:40px 20px;">
    <div style="margin-bottom:32px;">
        <span class="kicker">QUALIFICATION STAGE</span>
        <h1 class="display" style="font-size:clamp(28px,5vw,48px);margin-top:8px;">{{ $event->title }}</h1>
        <span class="mono dim" style="font-size:11px;">{{ $event->date_label }} · {{ $event->city }}</span>
    </div>

    @forelse($rounds as $round)
        <div style="margin-bottom:32px;">
            <div class="between" style="margin-bottom:14px;align-items:center;">
                <div class="col" style="gap:3px;">
                    <span class="label" style="font-size:18px;">{{ $round->name }}</span>
                    <span class="mono dim" style="font-size:10px;">ROUND {{ $round->round_number }} · {{ $round->pairing_type }} PAIRING</span>
                </div>
                <x-status-badge :status="$round->status" />
            </div>

            @forelse($round->qualificationMatches as $i => $match)
                <div class="panel" style="padding:16px 20px;margin-bottom:10px;">
                    <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;">
                        <div class="col" style="gap:4px;">
                            <span class="label" style="font-size:15px;">{{ $match->riderA?->name ?? 'TBD' }}</span>
                            <span class="mono dim" style="font-size:10px;">{{ $match->riderA?->city ?? '' }}</span>
                        </div>
                        <div class="center col" style="gap:6px;">
                            <span class="mono" style="font-size:12px;letter-spacing:0.15em;color:var(--ink-dim);">VS</span>
                            @if($match->trick)
                                <span class="badge badge-out" style="font-size:9px;">{{ $match->trick->name }} · {{ $match->trick->difficulty }}</span>
                            @endif
                            @if($match->winner_registration_id)
                                <span class="badge badge-lime" style="font-size:9px;">{{ $match->winner?->name }} WINS</span>
                            @endif
                        </div>
                        <div class="col" style="gap:4px;text-align:right;align-items:flex-end;">
                            <span class="label" style="font-size:15px;">{{ $match->riderB?->name ?? 'TBD' }}</span>
                            <span class="mono dim" style="font-size:10px;">{{ $match->riderB?->city ?? '' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="panel center" style="padding:28px;color:var(--ink-dim);font-size:13px;">
                    Pairings not yet assigned.
                </div>
            @endforelse
        </div>
    @empty
        <div class="panel center col" style="padding:60px;gap:16px;text-align:center;">
            <span style="font-size:44px;">⚡</span>
            <h3 class="display" style="font-size:26px;">Qualification Not Started</h3>
            <p class="dim">The qualification stage for this event hasn't been set up yet.</p>
        </div>
    @endforelse

    <div style="margin-top:32px;" class="flex gap-s">
        <a href="{{ route('events.show', $event->slug) }}" class="btn btn-ghost btn-sm">← Event Details</a>
        <a href="{{ route('bracket', $event->slug) }}" class="btn btn-ghost btn-sm">View Bracket →</a>
    </div>
</div>
