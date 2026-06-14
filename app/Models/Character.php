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

    public function translations()
    {
        return $this->hasMany(CharacterTranslation::class);
    }

    /** Resolve localized fields, falling back to the base row. */
    public function localized(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $t = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        return [
            'description'        => $t->description ?? $this->description,
            'personality_prompt' => $t->personality_prompt ?? $this->personality_prompt,
        ];
    }

    /** Overwrite description with the localized value for user-facing JSON; hide prompt + translations. */
    public function applyLocale(?string $locale = null): static
    {
        $l = $this->localized($locale);
        $this->description = $l['description'];
        $this->makeHidden(['personality_prompt', 'translations']);
        return $this;
    }
}
