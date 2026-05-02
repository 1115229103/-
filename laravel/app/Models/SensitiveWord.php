<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensitiveWord extends Model
{
    protected $table = 'sensitive_words';

    protected $fillable = ['word', 'category', 'severity', 'status'];
}
