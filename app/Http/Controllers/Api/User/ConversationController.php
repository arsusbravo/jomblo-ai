<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractMemoriesJob;
use App\Models\Character;
use App\Models\Conversation;
use App\Services\OpenRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->has('messages')
            ->with(['character', 'lastMessage'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['conversations' => $conversations]);
    }

    public function show(Request $request, int $characterId): JsonResponse
    {
        $character = Character::where('is_active', true)->findOrFail($characterId);

        $conversation = Conversation::firstOrCreate([
            'user_id'      => $request->user()->id,
            'character_id' => $character->id,
        ]);

        $conversation->load(['character.translations', 'messages']);
        $conversation->character->applyLocale();

        return response()->json([
            'conversation'          => $conversation,
            'free_images_remaining' => $request->user()->freeImagesRemainingThisPeriod(),
            'relationship_score'    => (int) $conversation->relationship_score,
            'relationship_level'    => $conversation->relationshipLevel(),
            'next_level_score'      => $this->nextLevelScore((int) $conversation->relationship_score),
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->canSendMessage()) {
            return response()->json(['message' => 'out_of_credits', 'credits_remaining' => 0], 402);
        }

        $request->validate([
            'messages'   => ['required', 'array', 'min:1', 'max:10'],
            'messages.*' => ['required', 'string', 'max:4000'],
        ]);

        $isFirstMessage = $conversation->messages()->doesntExist();
        $userMessages   = [];

        foreach ($request->messages as $content) {
            $userMessages[] = $conversation->messages()->create([
                'role'    => 'user',
                'content' => $content,
            ]);
        }

        if ($isFirstMessage) {
            $conversation->character()->increment('chatters_count');
        }

        // --- Streak & relationship score ---
        $today    = now()->toDateString();
        $lastDate = $conversation->last_streak_date?->toDateString();

        if ($lastDate === null || $lastDate < now()->subDay()->toDateString()) {
            $newStreakDays = 1;
            $streakBonus  = 0;
        } elseif ($lastDate === now()->subDay()->toDateString()) {
            $newStreakDays = $conversation->streak_days + 1;
            $streakBonus  = $newStreakDays >= 7 ? 5 : ($newStreakDays >= 4 ? 3 : 2);
        } else {
            // Same day — no streak bonus, keep existing streak
            $newStreakDays = $conversation->streak_days;
            $streakBonus  = 0;
        }

        $newScore     = $conversation->relationship_score + count($request->messages) + $streakBonus;
        $currentLevel = $conversation->relationshipLevel();
        $newLevel     = $this->scoreToLevel($newScore);
        $levelCrossed = $newLevel > $currentLevel && $newLevel > $conversation->unlocked_photo_level;

        // Inject a one-time milestone hint only when a level threshold is crossed.
        $milestoneHint = $levelCrossed
            ? '[Milestone] Your bond just deepened. Somewhere in your response, naturally hint that you want to share something more of yourself with the user — a photo, a moment. Keep it playful, warm, and completely in character. Do NOT break the fourth wall.'
            : null;

        // --- Build history & call AI ---
        $history = $conversation->messages()
            ->reorder()
            ->orderByDesc('created_at')
            ->limit(40)
            ->get()
            ->reverse()
            ->values();

        $conversation->load('character.translations');

        $aiText = app(OpenRouterService::class)->sendMessage(
            $conversation->character,
            $history,
            $user->name,
            $user->gender,
            $user,
            $milestoneHint,
        );

        // --- Split AI reply into bubbles ---
        $text      = trim($aiText);
        $totalLen  = mb_strlen($text);
        $sentences = preg_split('/[.!?]+\K\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        if (count($sentences) <= 1 || $totalLen < 120) {
            $parts = [$text];
        } else {
            $max = $totalLen > 300 ? 3 : 2;
            if (count($sentences) <= $max) {
                $parts = $sentences;
            } else {
                $parts   = array_slice($sentences, 0, $max - 1);
                $parts[] = implode(' ', array_slice($sentences, $max - 1));
            }
        }

        $aiMessages = [];
        foreach ($parts as $part) {
            $aiMessages[] = $conversation->messages()->create([
                'role'    => 'assistant',
                'content' => $part,
            ]);
        }

        // --- Memory extraction (every 6th user message, skip guests) ---
        if (! $user->isGuest()) {
            $userMessageCount = $conversation->messages()->where('role', 'user')->count();
            if ($userMessageCount % 6 === 0) {
                ExtractMemoriesJob::dispatch($conversation->id);
            }
        }

        // --- Update relationship score & streak ---
        $conversation->relationship_score = $newScore;
        $conversation->streak_days        = $newStreakDays;
        if ($lastDate !== $today) {
            $conversation->last_streak_date = $today;
        }

        $photoOfferMessage = null;

        if ($levelCrossed) {
            $conversation->unlocked_photo_level = $newLevel;
            $conversation->save();

            $availablePoses    = $this->posesForLevel($newLevel, $conversation->character->category);
            $photoOfferMessage = $conversation->messages()->create([
                'role'    => 'assistant',
                'type'    => 'text',
                'content' => '',
                'meta'    => [
                    'photo_offer'    => true,
                    'level'          => $newLevel,
                    'available_poses' => $availablePoses,
                ],
            ]);
        } else {
            $conversation->save();
        }

        // --- Credits ---
        if (! $user->hasUnlimited()) {
            $user->decrement('message_credits');
        }

        $fresh = $user->fresh();

        return response()->json([
            'user_messages'      => $userMessages,
            'ai_messages'        => $aiMessages,
            'photo_offer_message' => $photoOfferMessage,
            'credits_remaining'  => $fresh->message_credits,
            'unlimited_until'    => $fresh->unlimited_until,
            'relationship_score' => $conversation->relationship_score,
            'relationship_level' => $conversation->relationshipLevel(),
            'next_level_score'   => $this->nextLevelScore($conversation->relationship_score),
        ]);
    }

    public function clearMessages(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $conversation->messages()->delete();
        $conversation->touch();

        return response()->json(['cleared' => true]);
    }

    private function nextLevelScore(int $score): ?int
    {
        foreach ([20, 50, 100, 200] as $threshold) {
            if ($score < $threshold) return $threshold;
        }
        return null;
    }

    private function scoreToLevel(int $score): int
    {
        return match(true) {
            $score >= 200 => 4,
            $score >= 100 => 3,
            $score >= 50  => 2,
            $score >= 20  => 1,
            default       => 0,
        };
    }

    private function posesForLevel(int $level, string $category): array
    {
        $realistic = [
            1 => ['seductive', 'bikini'],
            2 => ['seductive', 'lingerie', 'bikini', 'lying'],
            3 => ['seductive', 'lingerie', 'bikini', 'lying', 'backpose', 'boudoir'],
            4 => ['seductive', 'lingerie', 'bikini', 'lying', 'backpose', 'boudoir'],
        ];
        $anime = [
            1 => ['selfie', 'winking', 'shy'],
            2 => ['selfie', 'winking', 'shy', 'action', 'sitting'],
            3 => ['selfie', 'winking', 'shy', 'action', 'sitting', 'portrait'],
            4 => ['selfie', 'winking', 'shy', 'action', 'sitting', 'portrait'],
        ];
        $map = $category === 'anime' ? $anime : $realistic;
        return $map[$level] ?? $map[4];
    }
}
