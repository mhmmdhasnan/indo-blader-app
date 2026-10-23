<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Sponsor extends Model
{
    protected $fillable = ['name', 'logo', 'sort_order', 'is_active', 'show_on_home'];

    protected $casts = [
        'is_active'    => 'boolean',
        'show_on_home' => 'boolean',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    public function placements()
    {
        return $this->hasMany(SponsorPlacement::class);
    }
}
