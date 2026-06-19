<?php

namespace App\Livewire;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\Event;
use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
class EventDetail extends Component
{
    public Event $event;
    public string $tab = 'OVERVIEW';

    public string $title = '';

    public function mount(string $slug): void
    {
        $this->event = Event::where('slug', $slug)->firstOrFail();
        $this->title = $this->event->title . ' — Indo Blader';
    }

    public function render()
    {
        $riders = Rider::whereJsonContains('achievements', fn($q) => true)
            ->orderByDesc('points')
            ->take(6)
            ->get();

        $bracket = Bracket::where('event_id', $this->event->id)->first();
        $schedule = $this->buildSchedule($bracket);

        $rules = [
            'Two timed runs per rider; best single run counts.',
            'Judging on Execution, Style, Creativity, and Difficulty (0–10 each).',
            'Mandatory protective gear in Vert and Park divisions.',
            'No re-runs except for verified course or equipment failure.',
            'Riders must check in 60 minutes before their heat.',
            'Head Judge decision is final on all scoring disputes.',
        ];

        $prizeSplit = [[1, 0.40], [2, 0.25], [3, 0.15], [4, 0.10], [5, 0.06], [6, 0.04]];

        return view('livewire.event-detail', compact('riders', 'schedule', 'rules', 'prizeSplit'))
            ->title($this->title);
    }

    private function buildSchedule(?Bracket $bracket): array
    {
        $roundLabels = [
            'PRELIM' => 'Preliminary', 'QF' => 'Quarter Final', 'SF' => 'Semi Final',
            'F' => 'Final', 'UB_R1' => 'Upper R1', 'UB_R2' => 'Upper R2',
            'UB_SF' => 'Upper Semi', 'UB_F' => 'Upper Final',
            'LB_R1' => 'Lower R1', 'LB_R2' => 'Lower R2', 'LB_R3' => 'Lower R3',
            'LB_R4' => 'Lower R4', 'LB_SF' => 'Lower Semi', 'LB_F' => 'Lower Final',
            'GF' => 'Grand Final',
        ];

        if ($bracket) {
            $matches = BracketMatch::where('bracket_id', $bracket->id)
                ->whereNotNull('submission_deadline')
                ->orderBy('submission_deadline')
                ->get();

            if ($matches->count()) {
                $byDate = $matches->groupBy(fn ($m) => $m->submission_deadline->format('Y-m-d'));
                $days   = [];
                $i      = 1;
                foreach ($byDate as $date => $dayMatches) {
                    $dt    = \Carbon\Carbon::parse($date);
                    $items = $dayMatches->map(fn ($m) => [
                        't'    => $m->submission_deadline->format('H:i'),
                        'name' => ($roundLabels[$m->round] ?? $m->round) . ' — Match ' . str_pad($m->match_number, 2, '0', STR_PAD_LEFT) . ' · Submission Deadline',
                        'tag'  => 'KNOCKOUT',
                    ])->values()->toArray();

                    $days[] = [
                        'day'   => 'DAY ' . $i . ' — ' . strtoupper($dt->format('D')),
                        'title' => $dt->format('d M Y'),
                        'items' => $items,
                    ];
                    $i++;
                }
                return $days;
            }
        }

        return [
            ['day' => 'DAY 1 — FRI', 'title' => 'Qualifiers', 'items' => [
                ['t' => '09:00', 'name' => 'Street Qualifiers — Heat 1–4', 'tag' => 'STREET'],
                ['t' => '13:00', 'name' => 'Park Qualifiers — Heat 1–3', 'tag' => 'PARK'],
                ['t' => '16:30', 'name' => 'Vert Qualifiers', 'tag' => 'VERT'],
            ]],
            ['day' => 'DAY 2 — SAT', 'title' => 'Semifinals', 'items' => [
                ['t' => '10:00', 'name' => 'Street Semifinal', 'tag' => 'STREET'],
                ['t' => '14:00', 'name' => 'Park Semifinal', 'tag' => 'PARK'],
                ['t' => '17:00', 'name' => 'Best Trick Battle', 'tag' => 'STREET'],
            ]],
            ['day' => 'DAY 3 — SUN', 'title' => 'Finals', 'items' => [
                ['t' => '11:00', 'name' => 'Park Final', 'tag' => 'PARK'],
                ['t' => '14:00', 'name' => 'Vert Final', 'tag' => 'VERT'],
                ['t' => '16:00', 'name' => 'Street Final', 'tag' => 'STREET'],
                ['t' => '18:30', 'name' => 'Awards & Closing', 'tag' => 'ALL'],
            ]],
        ];
    }
}
