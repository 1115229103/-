<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtitle extends Model
{
    protected $fillable = [
        'work_id', 'format', 'content', 'file_url', 'language',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
