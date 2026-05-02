<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'order_no', 'payment_method',
        'amount_cny', 'status', 'transaction_id', 'payment_data', 'paid_at',
    ];

    protected $casts = [
        'payment_data' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
