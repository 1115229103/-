<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'avatar_url', 'role', 'wrapped_dek',
    ];

    protected $hidden = [
        'password', 'remember_token', 'wrapped_dek',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function modelConfigs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserModelConfig::class);
    }

    public function works(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function membership(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Membership::class)->where('status', 'active');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }
}
