<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scene extends Model
{
    protected $fillable = [
        'work_id', 'name', 'location', 'time_of_day', 'indoor',
        'atmosphere', 'description',
    ];

    protected $casts = [
        'indoor' => 'boolean',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function storyboards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Storyboard::class);
    }
}
