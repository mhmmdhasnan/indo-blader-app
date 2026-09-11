<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DivisionGroup extends Model
{
    protected $fillable = ['event_division_id', 'name'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(EventDivision::class, 'event_division_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'division_group_id');
    }
}
