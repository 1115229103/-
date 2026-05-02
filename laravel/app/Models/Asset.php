<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'name', 'type', 'file_url', 'mime_type', 'file_size_bytes',
        'duration_sec', 'tags', 'sort_order', 'status',
    ];

    protected $casts = ['tags' => 'array'];
}
