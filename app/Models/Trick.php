<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trick extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'difficulty', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function qualificationMatches(): HasMany
    {
        return $this->hasMany(QualificationMatch::class);
    }

    public function bracketMatches(): HasMany
    {
        return $this->hasMany(BracketMatch::class);
    }
}
