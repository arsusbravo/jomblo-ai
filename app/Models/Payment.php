<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_session_id',
        'amount',
        'currency',
        'status',
        'credits_granted',
        'unlimited_granted_until',
    ];

    protected function casts(): array
    {
        return [
            'amount'                  => 'integer',
            'credits_granted'         => 'integer',
            'unlimited_granted_until' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
