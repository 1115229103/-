<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'work_id', 'name', 'gender', 'age_range', 'appearance',
        'personality', 'role_type', 'voice_id', 'reference_images',
    ];

    protected $casts = [
        'reference_images' => 'array',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
