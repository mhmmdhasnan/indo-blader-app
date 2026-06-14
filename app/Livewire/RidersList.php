<?php

namespace App\Livewire;

use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Riders — Indo Blader')]
class RidersList extends Component
{
    #[Url(as: 'cat')]
    public string $category = 'ALL';

    public function render()
    {
        $query = Rider::orderByDesc('points');

        if ($this->category !== 'ALL') {
            $query->where('category', $this->category);
        }

        return view('livewire.riders-list', ['riders' => $query->get()]);
    }
}
