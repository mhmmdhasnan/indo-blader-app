<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryReviewLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_category_id',
        'action',
        'from_category_id',
        'to_category_id',
        'performed_by',
        'notes',
    ];

    public function riderCategory(): BelongsTo
    {
        return $this->belongsTo(RiderCategory::class);
    }

    public function fromCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'from_category_id');
    }

    public function toCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'to_category_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
