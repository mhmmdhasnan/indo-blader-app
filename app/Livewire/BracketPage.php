<?php

namespace App\Livewire;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bracket — Indo Blader')]
class BracketPage extends Component
{
    public string $selectedSlug  = '';
    public string $selectedLevel = '';

    public function mount(string $slug = null): void
    {
        if ($slug) {
            $this->selectedSlug = $slug;
        } else {
            $event = Event::has('brackets')->orderByDesc('date')->first()
                ?? Event::orderByDesc('date')->first();
            $this->selectedSlug = $event?->slug ?? '';
        }

        $this->initLevel();
    }

    public function updatedSelectedSlug(): void
    {
        $this->initLevel();
    }

    private function initLevel(): void
    {
        $event = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;
        $levels = $event?->competition_levels ?? [];
        $this->selectedLevel = $levels[0] ?? '';
    }

    public function render()
    {
        $events  = Event::has('brackets')->orderByDesc('date')->get();
        $event   = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;

        $levels  = $event?->competition_levels ?? [];
        if ($this->selectedLevel === '' && count($levels)) {
            $this->selectedLevel = $levels[0];
        }

        $bracket = null;
        if ($event && $this->selectedLevel) {
            $bracket = Bracket::where('event_id', $event->id)
                ->where('competition_level', $this->selectedLevel)
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

        return view('livewire.bracket-page', compact('events', 'event', 'levels', 'bracket', 'matchesByRound', 'roundOrder'));
    }
}
