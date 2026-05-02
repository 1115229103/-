<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'templates';

    protected $fillable = [
        'name', 'category', 'content', 'preview_url', 'is_premium', 'sort_order', 'status',
    ];

    protected $casts = ['is_premium' => 'boolean'];
}
