<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorPlacement extends Model
{
    protected $fillable = ['sponsor_id', 'screen', 'x', 'y', 'size'];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
        'size' => 'integer',
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
}
