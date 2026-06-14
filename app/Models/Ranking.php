<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['rider_id', 'total_points', 'national_rank', 'updated_at'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }
}
