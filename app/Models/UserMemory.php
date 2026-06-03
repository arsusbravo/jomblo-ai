<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserMemory extends Model
{
    protected $fillable = [
        'user_id',
        'character_id',
        'key',
        'value',
        'category',
        'source_conversation_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function sourceConversation()
    {
        return $this->belongsTo(Conversation::class, 'source_conversation_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('character_id');
    }

    public function scopeForCharacter(Builder $query, int $characterId): Builder
    {
        return $query->where('character_id', $characterId);
    }
}
