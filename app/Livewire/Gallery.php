<?php

namespace App\Livewire;

use App\Models\GalleryItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Gallery — Indo Blader')]
class Gallery extends Component
{
    public ?int $lightbox = null;

    public function open(int $id): void
    {
        $this->lightbox = $id;
    }

    public function close(): void
    {
        $this->lightbox = null;
    }

    public function render()
    {
        $items = GalleryItem::orderBy('sort_order')->get();
        $active = $this->lightbox ? $items->firstWhere('id', $this->lightbox) : null;

        return view('livewire.gallery', compact('items', 'active'));
    }
}
