<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionTemplate extends Model
{
    protected $table = 'action_templates';

    protected $fillable = [
        'name', 'category', 'prompt_cn', 'prompt_en', 'tags', 'sort_order', 'status',
    ];

    protected $casts = ['tags' => 'array'];
}
