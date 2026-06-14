<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = [
        'entry_code', 'name', 'email', 'phone', 'dob', 'city', 'stance',
        'event_id', 'category', 'experience', 'ec_name', 'ec_phone', 'ec_relation',
        'payment_method', 'payment_proof', 'payment_status', 'status',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
