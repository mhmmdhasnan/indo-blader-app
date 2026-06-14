<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('About — Indo Blader')]
class About extends Component
{
    public function render()
    {
        return view('livewire.about');
    }
}
