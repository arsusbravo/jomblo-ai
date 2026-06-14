<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterTranslation extends Model
{
    protected $fillable = [
        'character_id',
        'locale',
        'description',
        'personality_prompt',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
