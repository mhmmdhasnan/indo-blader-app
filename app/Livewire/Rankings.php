<?php

namespace App\Livewire;

use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Rankings — Indo Blader')]
class Rankings extends Component
{
    #[Url(as: 'cat')]
    public string $category = 'ALL';

    public function render()
    {
        $query = Rider::orderByDesc('points');

        if ($this->category !== 'ALL') {
            $query->where('category', $this->category);
        }

        return view('livewire.rankings', ['riders' => $query->get()]);
    }
}
