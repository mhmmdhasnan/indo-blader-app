<?php

namespace App\Livewire;

use App\Models\Ranking;
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
        $rankedRiderIds = Ranking::orderBy('national_rank')->pluck('rider_id');

        if ($rankedRiderIds->isNotEmpty()) {
            $rankedRiders = Rider::whereIn('id', $rankedRiderIds)
                ->when($this->category !== 'ALL', fn ($q) => $q->where('category', $this->category))
                ->with('ranking')
                ->get()
                ->sortBy(fn ($r) => $r->ranking?->national_rank ?? PHP_INT_MAX)
                ->values();

            $unrankedRiders = Rider::whereNotIn('id', $rankedRiderIds)
                ->when($this->category !== 'ALL', fn ($q) => $q->where('category', $this->category))
                ->orderByDesc('points')
                ->get();

            $riders = $rankedRiders->merge($unrankedRiders);
        } else {
            $query = Rider::orderByDesc('points');
            if ($this->category !== 'ALL') {
                $query->where('category', $this->category);
            }
            $riders = $query->get();
        }

        return view('livewire.rankings', ['riders' => $riders]);
    }
}
