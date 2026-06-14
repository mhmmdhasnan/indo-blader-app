<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Rider;
use App\Models\Sponsor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Indo Blader — Aggressive Inline Indonesia')]
class Home extends Component
{
    public function render()
    {
        $featured   = Event::where('slug', 'nationals')->first();
        $events     = Event::orderBy('date')->take(3)->get();
        $topRiders  = Rider::orderByDesc('points')->take(6)->get();
        $featRiders = Rider::orderByDesc('points')->take(4)->get();
        $sponsors   = Sponsor::pluck('name');

        return view('livewire.home', compact('featured', 'events', 'topRiders', 'featRiders', 'sponsors'));
    }
}
