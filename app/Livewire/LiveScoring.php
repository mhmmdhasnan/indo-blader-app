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
        $current = null;
        $judgeScores = collect();

        if ($event) {
            $scores = JudgeScore::with('rider')
                ->where('event_id', $event->id)
                ->where('scoring_mode', 'LIVE')
                ->get()
                ->groupBy('rider_id')
                ->map(function ($riderScores) {
                    $rider  = $riderScores->first()->rider;
                    $run1   = $riderScores->where('run_number', 1)->sortByDesc('total')->first();
                    $run2   = $riderScores->where('run_number', 2)->sortByDesc('total')->first();
                    $best   = max($run1?->total ?? 0, $run2?->total ?? 0);
                    $status = $riderScores->contains('status', 'ON_COURSE') ? 'ON COURSE' : 'DONE';
                    return compact('rider', 'run1', 'run2', 'best', 'status');
                })
                ->filter(fn ($row) => $row['rider'] !== null)
                ->sortByDesc('best')
                ->values();

            $onCourse = JudgeScore::with('rider')
                ->where('event_id', $event->id)
                ->where('status', 'ON_COURSE')
                ->latest()
                ->first();
            $current = $onCourse?->rider;

            if ($current) {
                $judgeScores = JudgeScore::with(['judge', 'scoreDetails.criterion'])
                    ->where('event_id', $event->id)
                    ->where('rider_id', $current->id)
                    ->where('scoring_mode', 'LIVE')
                    ->whereNotNull('judge_user_id')
                    ->get();
            }
        }

        return view('livewire.live-scoring', compact('events', 'event', 'scores', 'current', 'judgeScores'));
    }
}
