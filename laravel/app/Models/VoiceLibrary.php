<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceLibrary extends Model
{
    protected $table = 'voice_library';

    protected $fillable = [
        'name', 'provider', 'provider_voice_id', 'gender',
        'language', 'style', 'sample_url', 'sort_order', 'status',
    ];
}
