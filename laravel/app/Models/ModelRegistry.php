<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelRegistry extends Model
{
    protected $table = 'model_registry';

    protected $fillable = [
        'category', 'model_name', 'display_name', 'provider',
        'api_type', 'base_url', 'request_path', 'default_params',
        'required_fields', 'description', 'docs_url', 'logo_url',
        'sort_order', 'status',
    ];

    protected $casts = [
        'default_params' => 'array',
        'required_fields' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function userConfigs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserModelConfig::class, 'model_registry_id');
    }
}
