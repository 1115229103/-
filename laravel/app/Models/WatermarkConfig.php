<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatermarkConfig extends Model
{
    protected $table = 'watermark_configs';

    protected $fillable = [
        'type', 'position', 'image_url', 'opacity',
        'width_percent', 'text', 'text_color', 'blind_enabled',
    ];

    protected $casts = [
        'blind_enabled' => 'boolean',
    ];
}
