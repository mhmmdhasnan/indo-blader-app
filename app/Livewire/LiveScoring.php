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
    public function render()
    {
        $event   = Event::where('slug', 'nationals')->first();
        $scores  = collect();
        $current = null;

        if ($event) {
            $scores = JudgeScore::with('rider')
                ->where('event_id', $event->id)
                ->get()
                ->groupBy('rider_id')
                ->map(function ($riderScores) {
                    $rider  = $riderScores->first()->rider;
                    $run1   = $riderScores->where('run_number', 1)->first();
                    $run2   = $riderScores->where('run_number', 2)->first();
                    $best   = max($run1?->total ?? 0, $run2?->total ?? 0);
                    $status = $riderScores->contains('status', 'ON_COURSE') ? 'ON COURSE' : 'DONE';
                    return compact('rider', 'run1', 'run2', 'best', 'status');
                })
                ->sortByDesc('best')
                ->values();

            $onCourse = JudgeScore::with('rider')->where('event_id', $event->id)->where('status', 'ON_COURSE')->first();
            $current  = $onCourse?->rider;
        }

        $trickFeed = [
            ['t' => '02:14', 'trick' => 'Switch Cab Royale',    'diff' => 'PRO',  'pts' => 9.5],
            ['t' => '01:58', 'trick' => 'True Spin Top Soul',   'diff' => 'HARD', 'pts' => 9.2],
            ['t' => '01:31', 'trick' => 'Gap to Mizou 270 Out', 'diff' => 'PRO',  'pts' => 9.6],
            ['t' => '00:52', 'trick' => 'Backslide to Fakie',   'diff' => 'MED',  'pts' => 8.8],
        ];

        $judges = [
            ['name' => 'A. Hidayat', 'seat' => 'HEAD JUDGE', 'exec' => 9.4, 'style' => 9.1, 'creativity' => 9.6],
            ['name' => 'M. Santoso', 'seat' => 'JUDGE 2',    'exec' => 9.2, 'style' => 9.4, 'creativity' => 9.3],
            ['name' => 'L. Pratiwi', 'seat' => 'JUDGE 3',    'exec' => 9.5, 'style' => 9.0, 'creativity' => 9.5],
            ['name' => 'K. Wijaya',  'seat' => 'JUDGE 4',    'exec' => 9.1, 'style' => 9.3, 'creativity' => 9.2],
        ];

        return view('livewire.live-scoring', compact('event', 'scores', 'current', 'trickFeed', 'judges'));
    }
}
