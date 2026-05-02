<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModelConfig extends Model
{
    protected $table = 'user_model_configs';

    protected $fillable = [
        'user_id', 'model_registry_id', 'category', 'stage',
        'api_key', 'custom_params', 'priority', 'status', 'last_verified_at',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'custom_params' => 'array',
        'last_verified_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ModelRegistry::class, 'model_registry_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary($query)
    {
        return $query->where('priority', 0);
    }

    public function scopeForStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Return masked API key for display (show last 4 chars only).
     */
    public function getMaskedKeyAttribute(): string
    {
        return '****' . substr($this->api_key, -4);
    }
}
