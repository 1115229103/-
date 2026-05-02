<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $table = 'backups';

    protected $fillable = [
        'type', 'file_path', 'file_size_bytes', 'status', 'error',
    ];
}
