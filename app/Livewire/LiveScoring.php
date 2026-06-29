<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Live Scoring — Indo Blader')]
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

    public function render()
    {
        $events  = Event::orderByRaw("FIELD(status,'LIVE','OPEN','CLOSING','SOON','FULL','DONE')")->orderBy('date', 'desc')->get();
        $event   = $this->selectedEventId ? Event::find($this->selectedEventId) : null;
        $scores  = collect();
        $judgeScores  = collect();
        $liveRider    = null;
        $displayPhase = null;
        $liveStartedAt = null;
        $runDuration  = $event?->run_duration ?? 60;
        $revealScore  = null;

        if ($event) {
            $scores = JudgeScore::with('rider')
                ->where('event_id', $event->id)
                ->where('scoring_mode', 'LIVE')
                ->get()
                ->groupBy('rider_id')
                ->map(function ($riderScores) {
                    $rider = $riderScores->first()->rider;
                    $run1  = $riderScores->where('run_number', 1)->sortByDesc('total')->first();
                    $run2  = $riderScores->where('run_number', 2)->sortByDesc('total')->first();
                    $best  = max($run1?->total ?? 0, $run2?->total ?? 0);
                    return compact('rider', 'run1', 'run2', 'best');
                })
                ->filter(fn ($row) => $row['rider'] !== null)
                ->sortByDesc('best')
                ->values();

            if ($event->live_phase) {
                $liveRider     = $event->live_rider_id ? Rider::find($event->live_rider_id) : null;
                $displayPhase  = $event->live_phase;
                $liveStartedAt = $event->live_started_at?->timestamp;

                if ($event->live_phase === 'REVEALING' && $event->live_rider_id && $event->live_run_number) {
                    $revealScore = JudgeScore::where('event_id', $event->id)
                        ->where('rider_id', $event->live_rider_id)
                        ->where('run_number', $event->live_run_number)
                        ->where('scoring_mode', 'LIVE')
                        ->where('status', 'DONE')
                        ->avg('total');
                }
            }

            if ($liveRider) {
                $judgeScores = JudgeScore::with(['judge', 'scoreDetails.criterion'])
                    ->where('event_id', $event->id)
                    ->where('rider_id', $liveRider->id)
                    ->where('scoring_mode', 'LIVE')
                    ->whereNotNull('judge_user_id')
                    ->get();
            }
        }

        return view('livewire.live-scoring', compact(
            'events', 'event', 'scores', 'judgeScores',
            'liveRider', 'displayPhase', 'liveStartedAt', 'runDuration', 'revealScore'
        ));
    }
}
