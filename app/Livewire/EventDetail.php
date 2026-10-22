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
        $this->title = $this->event->title . ' — FRAMEBLADESCORE';
    }

    public function render()
    {
        $riders = Rider::whereJsonContains('achievements', fn($q) => true)
            ->orderByDesc('points')
            ->take(6)
            ->get();

        $bracket = Bracket::where('event_id', $this->event->id)->first();
        $schedule = $this->event->schedule ? $this->buildScheduleFromEvent($this->event->schedule) : $this->buildSchedule($bracket);

        // Kosong = belum diisi admin; view menampilkan TBA.
        $rules = $this->event->rules ?: [];

        $prizeSplit = [[1, 0.40], [2, 0.25], [3, 0.15], [4, 0.10], [5, 0.06], [6, 0.04]];

        $divisionsByCategory = $this->event->divisions()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($div) => $div->discipline ?: 'OTHER');

        $competitors = collect();
        if ($this->tab === 'COMPETITOR') {
            $competitors = $this->event->registrations()
                ->with('division:id,name')
                ->where('status', '!=', 'REJECTED')
                ->orderBy('name')
                ->get(['id', 'event_id', 'division_id', 'name', 'city', 'status', 'photo'])
                ->groupBy(fn ($r) => $r->division?->name ?? 'Tanpa Divisi')
                ->sortKeys();
        }

        return view('livewire.event-detail', compact('riders', 'schedule', 'rules', 'prizeSplit', 'divisionsByCategory', 'competitors'))
            ->title($this->title);
    }

    private function buildScheduleFromEvent(array $items): array
    {
        $byDate = collect($items)
            ->filter(fn ($item) => !empty($item['date']))
            ->sortBy(fn ($item) => $item['date'] . ' ' . ($item['time'] ?? ''))
            ->groupBy('date');

        $days = [];
        $i = 1;
        foreach ($byDate as $date => $dayItems) {
            $dt = \Carbon\Carbon::parse($date);
            $days[] = [
                'day'   => 'DAY ' . $i . ' — ' . strtoupper($dt->format('D')),
                'title' => $dt->format('d M Y'),
                'items' => $dayItems->map(fn ($item) => [
                    't'    => $item['time'] ?: '—',
                    'name' => $item['title'],
                    'tag'  => $item['tag'] ?: 'ALL',
                ])->values()->toArray(),
            ];
            $i++;
        }

        return $days;
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

        // Belum ada jadwal: view menampilkan TBA.
        return [];
    }
}
