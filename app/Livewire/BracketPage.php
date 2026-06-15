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
    public ?Event $event   = null;
    public ?Bracket $bracket = null;

    public function mount(string $slug = null): void
    {
        if ($slug) {
            $this->event = Event::where('slug', $slug)->firstOrFail();
        } else {
            $this->event = Event::has('bracket')->orderByDesc('date')->first()
                ?? Event::orderByDesc('date')->first();
        }

        if ($this->event) {
            $this->bracket = Bracket::where('event_id', $this->event->id)->first();
        }
    }

    public function render()
    {
        $matchesByRound = collect();

        if ($this->bracket) {
            $matchesByRound = BracketMatch::with(['riderA', 'riderB', 'winner', 'trick'])
                ->where('bracket_id', $this->bracket->id)
                ->orderBy('match_number')
                ->get()
                ->groupBy('round');
        }

        $roundOrder = ['PRELIM', 'QF', 'SF', 'F', 'UB_R1', 'UB_R2', 'UB_SF', 'UB_F', 'LB_R1', 'LB_R2', 'LB_R3', 'LB_R4', 'LB_SF', 'LB_F', 'GF'];

        return view('livewire.bracket-page', compact('matchesByRound', 'roundOrder'));
    }
}
