<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    protected $table = 'prompt_templates';

    protected $fillable = [
        'stage', 'system_prompt', 'user_prompt_template',
        'output_schema', 'variables',
    ];

    protected $casts = [
        'output_schema' => 'array',
        'variables' => 'array',
    ];
}
