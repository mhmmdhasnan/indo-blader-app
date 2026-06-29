<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Events — Indo Blader')]
class EventsList extends Component
{
    #[Url(as: 'cat')]
    public string $category = 'ALL';

    public function render()
    {
        $query = Event::with('divisions')->orderBy('date');

        if ($this->category !== 'ALL') {
            $query->whereJsonContains('categories', $this->category);
        }

        return view('livewire.events-list', ['events' => $query->get()]);
    }
}
