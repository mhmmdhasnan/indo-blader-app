<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = ['type', 'height', 'label', 'caption', 'file_path', 'sort_order'];
}
