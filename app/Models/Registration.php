<?php

namespace App\Models;

use App\Models\EventDivision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $fillable = [
        'user_id', 'entry_code', 'name', 'email', 'phone', 'dob', 'city', 'stance',
        'event_id', 'division_id', 'category', 'competition_category', 'experience',
        'ec_name', 'ec_phone', 'ec_relation',
        'payment_method', 'payment_proof', 'payment_status', 'status',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(EventDivision::class, 'division_id');
    }

    public function riderCategory(): HasOne
    {
        return $this->hasOne(RiderCategory::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'notifiable_id');
    }

    public function battleSubmissions(): HasMany
    {
        return $this->hasMany(BattleSubmission::class);
    }

    public function getStatusVariantAttribute(): string
    {
        return match ($this->status) {
            'APPROVED' => 'lime',
            'REJECTED' => 'out',
            default    => 'red',
        };
    }

    public function getPaymentStatusVariantAttribute(): string
    {
        return match ($this->payment_status) {
            'VERIFIED' => 'lime',
            'UNPAID'   => 'out',
            default    => 'red',
        };
    }
}
