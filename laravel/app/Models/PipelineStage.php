<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    protected $table = 'pipeline_stages';

    protected $fillable = [
        'stage', 'name', 'category', 'is_required', 'is_enabled',
        'sort_order', 'description',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
