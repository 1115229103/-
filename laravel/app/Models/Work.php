<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Work extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'status', 'style', 'target_duration_sec',
        'pipeline_state', 'pipeline_progress', 'error_message',
    ];

    protected $casts = [
        'pipeline_progress' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function script(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Script::class);
    }

    public function characters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function scenes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Scene::class);
    }

    public function storyboards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Storyboard::class);
    }

    public function audioTracks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AudioTrack::class);
    }

    public function subtitles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subtitle::class);
    }

    public function exportTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExportTask::class);
    }
}
