<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Script extends Model
{
    protected $fillable = [
        'work_id', 'content', 'continuation', 'parsed_data',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
