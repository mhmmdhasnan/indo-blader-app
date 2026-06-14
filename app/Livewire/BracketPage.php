<?php

namespace App\Livewire;

use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bracket — Indo Blader')]
class BracketPage extends Component
{
    public function render()
    {
        $riders = Rider::pluck('name', 'slug')->all();

        $qf = [
            ['a' => 'rama',  'b' => 'bayu',  'sa' => 94.2, 'sb' => 86.2, 'w' => 'a'],
            ['a' => 'dimas', 'b' => 'fajar', 'sa' => 91.0, 'sb' => 88.4, 'w' => 'a'],
            ['a' => 'bagus', 'b' => 'arif',  'sa' => 93.2, 'sb' => 89.0, 'w' => 'a'],
            ['a' => 'reza',  'b' => 'galih', 'sa' => 90.1, 'sb' => 92.1, 'w' => 'b'],
        ];
        $sf = [
            ['a' => 'rama',  'b' => 'dimas', 'sa' => 96.4, 'sb' => 90.5, 'w' => 'a'],
            ['a' => 'bagus', 'b' => 'galih', 'sa' => 92.8, 'sb' => 91.0, 'w' => 'a'],
        ];
        $f = [
            ['a' => 'rama', 'b' => 'bagus', 'sa' => null, 'sb' => null, 'w' => null],
        ];

        return view('livewire.bracket-page', compact('qf', 'sf', 'f', 'riders'));
    }
}
