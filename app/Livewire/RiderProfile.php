<?php

namespace App\Livewire;

use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RiderProfile extends Component
{
    public Rider $rider;
    public int $rank = 1;

    public function mount(string $slug): void
    {
        $this->rider = Rider::where('slug', $slug)->firstOrFail();
        $this->rank  = Rider::orderByDesc('points')->pluck('id')->search($this->rider->id) + 1;
    }

    public function render()
    {
        return view('livewire.rider-profile')
            ->title($this->rider->name . ' — Indo Blader');
    }
}
