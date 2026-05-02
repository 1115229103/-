<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioTrack extends Model
{
    protected $fillable = [
        'work_id', 'storyboard_id', 'type', 'file_url',
        'duration_sec', 'volume', 'start_offset_sec',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
