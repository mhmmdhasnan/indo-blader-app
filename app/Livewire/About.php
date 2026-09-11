<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('About — FRAMEBLADESCORE')]
class About extends Component
{
    public function render()
    {
        return view('livewire.about');
    }
}
