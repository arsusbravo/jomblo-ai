<?php

namespace App\Services;

use App\Models\Character;
use App\Models\User;
use App\Models\UserMemory;

class MemoryService
{
    private const MAX_MEMORIES = 20;

    /**
     * Build a compact memory string for injection into the AI system prompt.
     *
     * Combines global memories (character_id IS NULL) and memories scoped to
     * the given character. Returns an empty string when no memories exist so
     * the caller can skip injection entirely.
     */
    public static function buildContext(User $user, ?Character $character): string
    {
        $memories = UserMemory::where('user_id', $user->id)
            ->where(function ($q) use ($character) {
                $q->whereNull('character_id');
                if ($character !== null) {
                    $q->orWhere('character_id', $character->id);
                }
            })
            ->orderBy('category')
            ->orderBy('created_at')
            ->limit(self::MAX_MEMORIES)
            ->get(['value']);

        if ($memories->isEmpty()) {
            return '';
        }

        $name      = trim((string) $user->name) !== '' ? $user->name : 'the user';
        $sentences = $memories->pluck('value')->implode(' ');

        return "[What you remember about {$name}: {$sentences}]";
    }
}
