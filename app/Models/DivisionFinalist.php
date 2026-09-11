<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionFinalist extends Model
{
    protected $fillable = ['event_division_id', 'registration_id'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(EventDivision::class, 'event_division_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
