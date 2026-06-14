<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\QualificationRound;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Qualification — Indo Blader')]
class QualificationPage extends Component
{
    public Event $event;

    public function mount(string $slug): void
    {
        $this->event = Event::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        $rounds = QualificationRound::with([
            'qualificationMatches.riderA',
            'qualificationMatches.riderB',
            'qualificationMatches.trick',
            'qualificationMatches.winner',
        ])
            ->where('event_id', $this->event->id)
            ->orderBy('round_number')
            ->get();

        return view('livewire.qualification-page', compact('rounds'));
    }
}
