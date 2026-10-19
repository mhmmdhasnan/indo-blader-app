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
        'event_id', 'division_id', 'division_group_id', 'category', 'competition_category', 'experience',
        'ec_name', 'ec_phone', 'ec_relation',
        'payment_method', 'payment_proof', 'photo', 'payment_status', 'status',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    /**
     * The event only stores which Rider is live, not which registration, and one
     * rider can be registered in several divisions — prefer the registration in
     * the event's active division so stage/label/scores land in the right one.
     */
    public static function forLiveRider(Event $event, Rider $rider): ?self
    {
        return static::where('event_id', $event->id)
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($rider) {
                if ($rider->user_id) {
                    $q->where('user_id', $rider->user_id)->orWhere('name', $rider->name);
                } else {
                    $q->where('name', $rider->name);
                }
            })
            ->with('division')
            ->when($event->active_division_id, fn ($q) => $q->orderByRaw('division_id = ? desc', [$event->active_division_id]))
            ->orderBy('id')
            ->first();
    }

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

    public function divisionGroup(): BelongsTo
    {
        return $this->belongsTo(DivisionGroup::class, 'division_group_id');
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

    /** Uploaded proofs live on the public disk; imported ones are external (Google Drive) links. */
    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof) {
            return null;
        }

        return str_starts_with($this->payment_proof, 'http')
            ? $this->payment_proof
            : asset('storage/' . $this->payment_proof);
    }

    /** Foto dari form register (disk public), atau link eksternal kalau hasil import. */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        return str_starts_with($this->photo, 'http') ? $this->photo : asset('storage/' . $this->photo);
    }

    public function getPaymentProofIsExternalAttribute(): bool
    {
        return (bool) $this->payment_proof && str_starts_with($this->payment_proof, 'http');
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
