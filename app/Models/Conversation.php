<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'character_id',
        'relationship_score',
        'streak_days',
        'last_streak_date',
        'unlocked_photo_level',
    ];

    protected $casts = [
        'last_streak_date' => 'date',
    ];

    public function relationshipLevel(): int
    {
        return match(true) {
            $this->relationship_score >= 200 => 4,
            $this->relationship_score >= 100 => 3,
            $this->relationship_score >= 50  => 2,
            $this->relationship_score >= 20  => 1,
            default                          => 0,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }
}
