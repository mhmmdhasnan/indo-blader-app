<?php

namespace App\Livewire;

use App\Models\DivisionGroup;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\JudgeScore;
use App\Models\Registration;
use App\Models\Rider;
use App\Services\LiveScoreboardService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Live Scoring — FRAMEBLADESCORE')]
class LiveScoring extends Component
{
    public int $selectedEventId = 0;

    public function mount(): void
    {
        $active = Event::where('status', 'LIVE')->orderBy('date')->first()
            ?? Event::orderByRaw("ABS(DATEDIFF(date, NOW()))")->orderBy('date')->first()
            ?? Event::orderBy('date')->first();

        if ($active) {
            $this->selectedEventId = $active->id;
        }
    }

    private function resolveLiveRiderDivision(Event $event, Rider $liveRider): ?EventDivision
    {
        $reg = Registration::where('event_id', $event->id)
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($liveRider) {
                if ($liveRider->user_id) {
                    $q->where('user_id', $liveRider->user_id)
                      ->orWhere('name', $liveRider->name);
                } else {
                    $q->where('name', $liveRider->name);
                }
            })
            ->with('division')
            ->first();

        return $reg?->division;
    }

    public function render()
    {
        $events   = Event::orderByRaw("FIELD(status,'LIVE','OPEN','CLOSING','SOON','FULL','DONE')")->orderBy('date', 'desc')->get();
        $event    = $this->selectedEventId ? Event::find($this->selectedEventId) : null;
        $divisions = $event ? EventDivision::where('event_id', $event->id)->where('is_active', true)->orderBy('name')->get() : collect();

        // Leaderboard selalu mengikuti divisi/group yang sedang aktif dipilih Head Judge.
        $division = $event?->active_division_id
            ? $divisions->firstWhere('id', $event->active_division_id)
            : $divisions->first();
        $stage = $division?->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION';

        $groups = ($division && $stage === 'QUALIFICATION')
            ? DivisionGroup::where('event_division_id', $division->id)->orderBy('name')->get()
            : collect();

        $selectedGroupId = $event?->active_group_id ?? 0;

        $scores       = collect();
        $judgeScores  = collect();
        $liveRider    = null;
        $liveStage    = 'QUALIFICATION';
        $displayPhase = null;
        $liveStartedAt = null;
        $runDuration  = $event?->run_duration ?? 60;
        $revealScore  = null;
        $liveDivisionLabel = $event?->title ?? 'FRAMEBLADESCORE';

        if ($event) {
            if ($divisions->isNotEmpty() && $division) {
                $groupId = ($stage === 'QUALIFICATION' && $groups->contains('id', $selectedGroupId))
                    ? $selectedGroupId
                    : null;
                $scores = app(LiveScoreboardService::class)->buildLeaderboard($division, $stage, $groupId);
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

            if ($event->live_phase) {
                $liveRider     = $event->live_rider_id ? Rider::find($event->live_rider_id) : null;
                $displayPhase  = $event->live_phase;
                $liveStartedAt = $event->live_started_at?->timestamp;
            }

            $liveDivision = $liveRider ? $this->resolveLiveRiderDivision($event, $liveRider) : null;
            $liveStage    = $liveDivision?->live_stage ?? 'QUALIFICATION';
            $liveDivisionLabel = $liveDivision
                ? strtoupper($liveDivision->name) . ' · ' . ($liveDivision->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI')
                : strtoupper($event->title ?? 'FRAMEBLADESCORE');

            if ($event->live_phase === 'REVEALING' && $event->live_rider_id && $event->live_run_number) {
                $revealScore = JudgeScore::where('event_id', $event->id)
                    ->where('rider_id', $event->live_rider_id)
                    ->where('run_number', $event->live_run_number)
                    ->where('scoring_mode', 'LIVE')
                    ->where('live_stage', $liveStage)
                    ->where('status', 'DONE')
                    ->avg('total');
            }

            if ($liveRider) {
                $judgeScores = JudgeScore::with(['judge', 'scoreDetails.criterion'])
                    ->where('event_id', $event->id)
                    ->where('rider_id', $liveRider->id)
                    ->where('scoring_mode', 'LIVE')
                    ->where('live_stage', $liveStage)
                    ->whereNotNull('judge_user_id')
                    ->get();
            }
        }

        return view('livewire.live-scoring', compact(
            'events', 'event', 'divisions', 'division', 'stage', 'groups', 'selectedGroupId', 'scores', 'judgeScores',
            'liveRider', 'displayPhase', 'liveStartedAt', 'runDuration', 'revealScore', 'liveDivisionLabel'
        ));
    }
}
