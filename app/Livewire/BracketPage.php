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
    public string $selectedSlug = '';

    public function mount(string $slug = null): void
    {
        if ($slug) {
            $this->selectedSlug = $slug;
        } else {
            $event = Event::has('bracket')->orderByDesc('date')->first()
                ?? Event::orderByDesc('date')->first();
            $this->selectedSlug = $event?->slug ?? '';
        }
    }

    public function render()
    {
        $events  = Event::has('bracket')->orderByDesc('date')->get();
        $event   = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;
        $bracket = $event ? Bracket::where('event_id', $event->id)->first() : null;

        $matchesByRound = collect();
        if ($bracket) {
            $matchesByRound = BracketMatch::with(['riderA', 'riderB', 'winner', 'trick'])
                ->where('bracket_id', $bracket->id)
                ->orderBy('match_number')
                ->get()
                ->groupBy('round');
        }

        $roundOrder = ['PRELIM', 'QF', 'SF', 'F', 'UB_R1', 'UB_R2', 'UB_SF', 'UB_F', 'LB_R1', 'LB_R2', 'LB_R3', 'LB_R4', 'LB_SF', 'LB_F', 'GF'];

        return view('livewire.bracket-page', compact('events', 'event', 'bracket', 'matchesByRound', 'roundOrder'));
    }
}
