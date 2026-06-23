<?php

namespace App\Livewire;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\Event;
use App\Models\EventDivision;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bracket — Indo Blader')]
class BracketPage extends Component
{
    public string $selectedSlug       = '';
    public int    $selectedDivisionId = 0;

    public function mount(string $slug = null): void
    {
        if ($slug) {
            $this->selectedSlug = $slug;
        } else {
            $event = Event::has('brackets')->orderByDesc('date')->first()
                ?? Event::orderByDesc('date')->first();
            $this->selectedSlug = $event?->slug ?? '';
        }

        $this->initDivision();
    }

    public function updatedSelectedSlug(): void
    {
        $this->initDivision();
    }

    private function initDivision(): void
    {
        $event = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;
        $firstBracket = $event ? Bracket::where('event_id', $event->id)->first() : null;
        $this->selectedDivisionId = $firstBracket?->division_id ?? 0;
    }

    public function render()
    {
        $events = Event::has('brackets')->orderByDesc('date')->get();
        $event  = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;

        // All divisions that have a bracket for this event
        $divisions = $event
            ? EventDivision::whereHas('brackets', fn($q) => $q->where('event_id', $event->id))
                ->where('event_id', $event->id)
                ->orderBy('name')
                ->get()
            : collect();

        if ($this->selectedDivisionId === 0 && $divisions->count()) {
            $this->selectedDivisionId = $divisions->first()->id;
        }

        $bracket = null;
        if ($event && $this->selectedDivisionId) {
            $bracket = Bracket::where('event_id', $event->id)
                ->where('division_id', $this->selectedDivisionId)
                ->first();
        } elseif ($event) {
            $bracket = Bracket::where('event_id', $event->id)->first();
        }

        $matchesByRound = collect();
        if ($bracket) {
            $matchesByRound = BracketMatch::with(['riderA', 'riderB', 'winner', 'trick'])
                ->where('bracket_id', $bracket->id)
                ->orderBy('match_number')
                ->get()
                ->groupBy('round');
        }

        $roundOrder = ['PRELIM', 'QF', 'SF', 'F', 'UB_R1', 'UB_R2', 'UB_SF', 'UB_F', 'LB_R1', 'LB_R2', 'LB_R3', 'LB_R4', 'LB_SF', 'LB_F', 'GF'];

        return view('livewire.bracket-page', compact('events', 'event', 'divisions', 'bracket', 'matchesByRound', 'roundOrder'));
    }
}
