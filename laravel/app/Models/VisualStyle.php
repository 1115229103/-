<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisualStyle extends Model
{
    protected $table = 'visual_styles';

    protected $fillable = [
        'name', 'category', 'prompt_keyword', 'preview_url', 'sort_order', 'status',
    ];
}
