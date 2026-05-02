<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Storyboard extends Model
{
    protected $fillable = [
        'work_id', 'shot_number', 'shot_type', 'camera_movement',
        'duration_sec', 'scene_id', 'characters_in_frame',
        'action_description', 'dialogue', 'emotion',
        'transition_to_next', 'notes', 'image_url', 'video_url',
        'status', 'error_message', 'retry_count',
    ];

    protected $casts = [
        'characters_in_frame' => 'array',
    ];

    public function work(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function scene(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function canRetry(): bool
    {
        return $this->retry_count < 3;
    }
}
