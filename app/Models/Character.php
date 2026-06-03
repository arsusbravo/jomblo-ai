<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'category',
        'description',
        'personality_prompt',
        'avatar_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/' . $this->avatar_path) : null;
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
