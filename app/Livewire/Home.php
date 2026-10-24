<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\Sponsor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('FRAMEBLADESCORE')]
class Home extends Component
{
    public function render()
    {
        $featured   = Event::with('divisions')->where('featured', true)->orderByDesc('date')->first();
        $events     = Event::with('divisions')->whereIn('status', ['LIVE', 'OPEN', 'SOON'])->orderBy('date')->take(3)->get();
        $topRiders  = Rider::orderByDesc('points')->take(6)->get();
        $sponsors   = Sponsor::where('is_active', true)->where('show_on_home', true)->orderBy('sort_order')->orderBy('name')->get();

        $marquee    = Setting::marquee();

        return view('livewire.home', compact('featured', 'events', 'topRiders', 'sponsors', 'marquee'));
    }
}
