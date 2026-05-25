<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_guest',
        'gender',
        'message_credits',
        'unlimited_until',
        'stripe_customer_id',
        'date_of_birth',
        'provider',
        'provider_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_guest' => 'boolean',
            'message_credits' => 'integer',
            'unlimited_until' => 'datetime',
            'date_of_birth'   => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest;
    }

    public function hasCredits(): bool
    {
        return $this->message_credits > 0;
    }

    public function hasUnlimited(): bool
    {
        return $this->unlimited_until !== null && $this->unlimited_until->isFuture();
    }

    public function canSendMessage(): bool
    {
        return $this->hasUnlimited() || $this->hasCredits();
    }

    protected $appends = ['unread_support_count'];

    public function getUnreadSupportCountAttribute(): int
    {
        return $this->hasMany(SupportMessage::class)
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->count();
    }

    public function supportMessages()
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
