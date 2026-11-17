<?php

namespace App\Livewire;

use App\Events\LiveScoreUpdated;
use App\Models\DivisionFinalist;
use App\Models\DivisionGroup;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventJudgeAssignment;
use App\Models\JudgeScore;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\SponsorPlacement;
use App\Services\LiveScoreboardService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Live Scoring — FRAMEBLADESCORE')]
class LiveScoring extends Component
{
    /**
     * Bound at mount() so the #[On] echo listener below can subscribe to the
     * right public channel — the rest of the component still recomputes the
     * active event fresh on every render(), this is only for the channel name.
     */
    public int $eventId = 0;

    public function mount(): void
    {
        $this->eventId = $this->resolveActiveEvent()?->id ?? 0;
    }

    private function resolveActiveEvent(): ?Event
    {
        $settingId = Setting::get('active_event_id');
        if ($settingId && $event = Event::find($settingId)) {
            return $event;
        }

        return Event::where('status', 'LIVE')->orderBy('date')->first()
            ?? Event::orderByRaw("ABS(DATEDIFF(date, NOW()))")->orderBy('date')->first()
            ?? Event::orderBy('date')->first();
    }

    private function resolveLiveRiderDivision(Event $event, Rider $liveRider): ?EventDivision
    {
        $reg = Registration::forLiveRider($event, $liveRider);

        return $reg?->division;
    }

    /**
     * Every spectator sees the same screen, so a burst of N viewers refreshing
     * after one score change should cost one build, not N. The payload is cached
     * under the version LiveScoreUpdated::push() bumps on every change (a stale
     * in-flight build lands under the old version and is never served), and a
     * lock makes concurrent misses wait for the first builder instead of all
     * hitting MySQL. The short TTL covers changes that don't push (sponsors,
     * active event setting, title, ...).
     */
    public function render()
    {
        // Admin bisa mengganti event aktif saat /live sudah terbuka: ikuti, supaya klien pindah channel.
        $this->eventId = $this->resolveActiveEvent()?->id ?? 0;

        $cache = Cache::store('file');
        $key   = 'live-scoring:view:' . LiveScoreUpdated::version();

        // Stored as a pre-serialized string: Laravel 13's cache refuses to
        // unserialize Eloquent models by default (they'd come back as incomplete
        // objects), and this payload is only ever written by this method.
        $raw = $cache->get($key);
        if ($raw === null) {
            $raw = $cache->lock($key . ':lock', 10)->block(8, function () use ($cache, $key) {
                $hit = $cache->get($key);
                if ($hit !== null) {
                    return $hit;
                }
                $built = serialize($this->buildViewData());
                $cache->put($key, $built, 5);

                return $built;
            });
        }

        // serverNow sengaja di luar cache: dipakai klien untuk menyinkronkan jam timer dengan server.
        return view('livewire.live-scoring', unserialize($raw) + ['serverNow' => (int) round(microtime(true) * 1000)]);
    }

    private function buildViewData(): array
    {
        $event      = $this->resolveActiveEvent();
        $idleScreen = (bool) $event?->idle_screen;
        $idlePlacements   = SponsorPlacement::with('sponsor')->where('screen', 'idle')
            ->whereHas('sponsor', fn ($q) => $q->where('is_active', true))->get();
        $nextupPlacements = SponsorPlacement::with('sponsor')->where('screen', 'nextup')
            ->whereHas('sponsor', fn ($q) => $q->where('is_active', true))->get();
        $leaderboardPlacements = SponsorPlacement::with('sponsor')->where('screen', 'leaderboard')
            ->whereHas('sponsor', fn ($q) => $q->where('is_active', true))->get();
        $divisions  = $event ? EventDivision::where('event_id', $event->id)->where('is_active', true)->orderBy('name')->get() : collect();

        // Leaderboard selalu mengikuti divisi/group yang sedang aktif dipilih Head Judge.
        $division = $event?->active_division_id
            ? $divisions->firstWhere('id', $event->active_division_id)
            : $divisions->first();
        $stage = $division?->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION';

        // Heat tahap sekarang: group kualifikasi, atau heat final kalau final dibagi heat.
        $groups = $division ? $division->stageGroups() : collect();

        $selectedGroupId = $event?->active_group_id ?? 0;

        $scores        = collect();
        $scoreSections = collect();
        $judgeScores  = collect();
        $liveRider    = null;
        $liveStage    = 'QUALIFICATION';
        $displayPhase = null;
        $liveStartedAt = null;
        $runDuration  = $event?->run_duration ?? 60;
        $revealScore  = null;
        $revealAccumulatedTotal = null;
        $liveRiderBestScore = null;
        $liveDivisionLabel = $event?->title ?? 'FRAMEBLADESCORE';
        $isBestTrickPhase = false;
        $liveRiderPhoto   = null;
        $showingQualRecap = false;
        $jamActive   = false;
        $jamRows     = collect();
        $jamDuration = $event?->live_duration ?? 180;
        $heatRail    = [];   // rel heat di phase banner: [['name','state' => done|current|upcoming], ...]
        $jamJudges   = [];   // progres juri saat TIME'S UP: [['n','done'], ...]
        $nextHeat    = null; // ['label' => 'HEAT 4', 'names' => 'A · B'] — heat berikutnya (UP NEXT)

        // Pengumuman kualifikasi & final ini SENGAJA independen dari $stage saat ini —
        // begitu admin pilih finalis, live_stage divisi langsung pindah ke FINAL, jadi
        // banner "siapa yang lolos" harus tetap tampil terus meski leaderboard yang
        // ditampilkan sekarang sudah leaderboard FINAL, bukan cuma saat $stage masih
        // QUALIFICATION (yang mana udah kepakai duluan sebelum operator sempat klik
        // tombol umumkan).
        $qualificationAnnounced = (bool) $division?->qualification_announced_at;
        $finalAnnounced         = (bool) $division?->final_announced_at;
        $finalistRegIds         = ($division && $qualificationAnnounced)
            ? DivisionFinalist::where('event_division_id', $division->id)->pluck('registration_id')
            : collect();
        $finalistNames = $finalistRegIds->isNotEmpty()
            ? Registration::whereIn('id', $finalistRegIds)->orderBy('name')->pluck('name')
            : collect();
        $podium = ($division && $finalAnnounced)
            ? app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL')->take(3)->values()
            : collect();

        if ($event) {
            if ($divisions->isNotEmpty() && $division) {
                // Selama Best Trick aktif, leaderboard publik = gabungan semua finalis (bukan satu heat final).
                $groupId = (!$division->best_trick_active && $groups->contains('id', $selectedGroupId))
                    ? $selectedGroupId
                    : null;

                if ($stage === 'QUALIFICATION' && $groupId === null && $groups->isNotEmpty()) {
                    // "Semua" group dipilih & divisi ini punya group — 1 group = 1 tabel,
                    // sama seperti tampilan leaderboard di Judge Panel Operator.
                    $scoreSections = app(LiveScoreboardService::class)->buildQualificationSections($division);
                } else {
                    $scores = app(LiveScoreboardService::class)->buildLeaderboard($division, $stage, $groupId);
                }

                // Begitu admin pindah ke FINAL, leaderboard yang tampil ke publik berubah
                // jadi leaderboard FINAL (skor final, bukan kualifikasi lagi). Tapi kalau
                // Operator sudah mengumumkan hasil kualifikasi, yang mau ditampilkan di
                // panel resting-state ini adalah rekap "siapa yang lanjut ke final beserta
                // skor kualifikasi mereka" — bukan progres skor final (progres final tetap
                // kelihatan lewat overlay saat run-nya aktif/di-reveal).
                if ($stage === 'FINAL' && $qualificationAnnounced && $finalistRegIds->isNotEmpty() && $event->live_phase !== 'NEXT') {
                    $scores = app(LiveScoreboardService::class)->buildLeaderboard($division, 'QUALIFICATION', null)
                        ->filter(fn ($row) => $finalistRegIds->contains($row['registration']->id))
                        ->values();
                    $showingQualRecap = true;
                }
            } elseif ($divisions->isEmpty()) {
                // Backward-compat: LIVE_SCORE events with no EventDivision rows yet
                // keep the original flat, whole-event, stage-less leaderboard.
                $scores = JudgeScore::with('rider')
                    ->where('event_id', $event->id)
                    ->where('scoring_mode', 'LIVE')
                    ->where('status', 'DONE')
                    ->get()
                    ->groupBy('rider_id')
                    ->map(function ($riderScores) {
                        $rider   = $riderScores->first()->rider;
                        $run1avg = $riderScores->where('run_number', 1)->avg('total');
                        $run2avg = $riderScores->where('run_number', 2)->avg('total');
                        $best    = max($run1avg ?? 0, $run2avg ?? 0);
                        return [
                            'rider' => $rider,
                            'run1'  => $run1avg !== null ? round($run1avg, 1) : null,
                            'run2'  => $run2avg !== null ? round($run2avg, 1) : null,
                            'best'  => round($best, 1),
                        ];
                    })
                    ->filter(fn ($row) => $row['rider'] !== null)
                    ->sortByDesc('best')
                    ->values();
            }

            // Heat rail (divisi dengan >1 group/heat saat kualifikasi): ✓ selesai · ● sedang dipilih · sisanya menunggu.
            if ($division && $groups->count() > 1) {
                $railSections = ($stage === 'QUALIFICATION')
                    ? ($scoreSections->isNotEmpty() ? $scoreSections : app(LiveScoreboardService::class)->buildQualificationSections($division))
                    : $groups->map(fn ($g) => ['group' => $g, 'leaderboard' => app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL', $g->id)]);
                $heatRail = $railSections->filter(fn ($sec) => $sec['group'] !== null)->values()->map(function ($sec, $i) use ($selectedGroupId, $division) {
                    $done = $sec['leaderboard']->isNotEmpty()
                        && $sec['leaderboard']->every(fn ($row) => $row['run1'] !== null && ($division->format === 'JAMMING' || $row['run2'] !== null));   // selesai = semua rider sudah punya Run 1 DAN Run 2

                    return [
                        'name'  => 'H' . ($i + 1),
                        'state' => $done ? 'done' : ((int) $sec['group']->id === (int) $selectedGroupId ? 'current' : 'upcoming'),
                    ];
                })->all();
            }

            // Heat Jamming Season: tidak ada rider tunggal — seluruh heat tampil bersama
            // (timer + ranking heat), jadi kartu esports per-rider dilewati.
            $jamActive = (bool) ($event->live_jam && $event->live_phase && $division?->format === 'JAMMING');
            if ($event->live_phase && !$jamActive) {
                $liveRider     = $event->live_rider_id ? Rider::find($event->live_rider_id) : null;
                $displayPhase  = $event->live_phase;
                $liveStartedAt = $event->live_started_at?->timestamp;
            }

            if ($jamActive) {
                $jamRows       = app(LiveScoreboardService::class)->buildLeaderboard(
                    $division, $stage, $groups->contains('id', $selectedGroupId) ? $selectedGroupId : null
                );
                $displayPhase  = $event->live_phase;
                $liveStartedAt = $event->live_started_at?->timestamp;

                // Progres juri: juri "selesai" kalau sudah menilai semua rider di heat ini (run yang sedang jalan).
                if ($groups->count() > 1 && $selectedGroupId) {
                    $idx       = $groups->search(fn ($g) => (int) $g->id === (int) $selectedGroupId);
                    $nextGroup = $idx !== false ? $groups->get($idx + 1) : null;
                    if ($nextGroup) {
                        $nextHeat = [
                            'label' => ($stage === 'FINAL' ? 'FINAL HEAT ' : 'HEAT ') . ($idx + 2),
                            'names' => app(LiveScoreboardService::class)
                                ->buildLeaderboard($division, $stage, $nextGroup->id)
                                ->map(fn ($r) => $r['rider']->name)->sort(SORT_NATURAL | SORT_FLAG_CASE)->take(8)->implode(' · '),
                        ];
                    }
                }

                $heatSize = $jamRows->count();
                if ($heatSize > 0) {
                    $progress = JudgeScore::where('event_id', $event->id)
                        ->where('division_id', $division->id)
                        ->whereIn('rider_id', $jamRows->map(fn ($r) => $r['rider']->id)->filter()->values())
                        ->where('run_number', $event->live_run_number ?: 1)
                        ->where('scoring_mode', 'LIVE')
                        ->where('live_stage', $stage)
                        ->where('status', 'DONE')
                        ->selectRaw('judge_user_id, count(*) as c')->groupBy('judge_user_id')
                        ->pluck('c', 'judge_user_id');
                    $jamJudges = EventJudgeAssignment::where('event_id', $event->id)->with('user')->orderBy('id')->get()->sortBy(fn ($aj) => $aj->user?->role === 'head_judge' ? 0 : 1)->pluck('user_id')
                        ->values()
                        ->map(fn ($uid, $i) => ['n' => $i + 1, 'done' => ($progress[$uid] ?? 0) >= $heatSize])
                        ->all();
                }
            }

            $liveDivision = $liveRider ? $this->resolveLiveRiderDivision($event, $liveRider) : null;
            // Foto: utamakan yang diunggah saat registrasi (event ini), baru avatar profil rider.
            $liveRiderPhoto = $liveRider
                ? (Registration::forLiveRider($event, $liveRider)?->photo_url
                    ?: ($liveRider->avatar ? asset('storage/' . $liveRider->avatar) : null))
                : null;
            $liveStage    = $liveDivision?->live_stage ?? 'QUALIFICATION';
            $isBestTrickPhase = $liveDivision?->best_trick_active ?? false;
            $liveScoringMode  = $isBestTrickPhase ? 'BEST_TRICK' : 'LIVE';
            $liveDivisionLabel = $liveDivision
                ? strtoupper($liveDivision->name) . ' · ' . ($isBestTrickPhase ? 'BEST TRICK' : ($liveDivision->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI'))
                : strtoupper($event->title ?? 'FRAMEBLADESCORE');

            if ($event->live_phase === 'REVEALING' && $event->live_rider_id && $event->live_run_number) {
                $revealScore = JudgeScore::where('event_id', $event->id)
                    ->where('division_id', $liveDivision?->id)
                    ->where('rider_id', $event->live_rider_id)
                    ->where('run_number', $event->live_run_number)
                    ->where('scoring_mode', $liveScoringMode)
                    ->where('live_stage', $liveStage)
                    ->where('status', 'DONE')
                    ->avg('total');

                // The number that lands on the leaderboard is the rider's accumulated
                // total (best-of-runs + best-trick bonus), not just this one run's raw
                // judged average — show that as the headline reveal number.
                if ($liveDivision) {
                    $revealAccumulatedTotal = app(LiveScoreboardService::class)
                        ->buildLeaderboard($liveDivision, $liveStage)
                        ->first(fn ($row) => $row['rider']->id === $liveRider->id)['total'] ?? null;
                }
            }

            if ($liveRider) {
                $judgeScores = JudgeScore::with(['judge', 'scoreDetails.criterion'])
                    ->where('event_id', $event->id)
                    ->where('division_id', $liveDivision?->id)
                    ->where('rider_id', $liveRider->id)
                    ->where('scoring_mode', $liveScoringMode)
                    ->where('live_stage', $liveStage)
                    ->whereNotNull('judge_user_id')
                    ->get()
                    ->sortBy(fn ($sc) => $sc->judge?->role === 'head_judge' ? 0 : 1)
                    ->values();

                // Saat REVEALING, "skor tertinggi sebelumnya" harus mengecualikan run
                // yang baru saja diungkap ini sendiri — kalau tidak, run pertama
                // akan selalu "mengalahkan dirinya sendiri" dan terlihat aneh.
                $excludeRunNumber = $event->live_phase === 'REVEALING' ? $event->live_run_number : null;

                $liveRiderBestScore = JudgeScore::where('event_id', $event->id)
                    ->where('division_id', $liveDivision?->id)
                    ->where('rider_id', $liveRider->id)
                    ->where('scoring_mode', $liveScoringMode)
                    ->where('live_stage', $liveStage)
                    ->where('status', 'DONE')
                    ->when($excludeRunNumber, fn ($q) => $q->where('run_number', '!=', $excludeRunNumber))
                    ->get()
                    ->groupBy('run_number')
                    ->map(fn ($runScores) => $runScores->avg('total'))
                    ->max();

                $liveRiderBestScore = $liveRiderBestScore > 0 ? round($liveRiderBestScore, 1) : null;
            }
        }

        return compact(
            'event', 'idleScreen', 'idlePlacements', 'nextupPlacements', 'leaderboardPlacements', 'divisions', 'division', 'stage', 'groups', 'selectedGroupId', 'scores', 'scoreSections', 'judgeScores',
            'liveRider', 'liveRiderPhoto', 'displayPhase', 'liveStartedAt', 'runDuration', 'revealScore', 'revealAccumulatedTotal',
            'liveRiderBestScore', 'liveDivisionLabel', 'isBestTrickPhase', 'showingQualRecap',
            'qualificationAnnounced', 'finalAnnounced', 'finalistRegIds', 'finalistNames', 'podium', 'jamActive', 'jamDuration', 'jamRows', 'heatRail', 'jamJudges', 'nextHeat'
        );
    }
}
