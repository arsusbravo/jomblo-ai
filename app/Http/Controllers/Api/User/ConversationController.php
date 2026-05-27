<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
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
            'user_id' => $request->user()->id,
            'character_id' => $character->id,
        ]);

        $conversation->load(['character', 'messages']);

        return response()->json([
            'conversation'          => $conversation,
            'free_images_remaining' => $request->user()->freeImagesRemainingThisPeriod(),
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
            'content' => ['required', 'string', 'max:4000'],
        ]);

        // Capture "this is the user's first ever message to this character"
        // BEFORE saving — bump the persistent lifetime counter once.
        $isFirstMessage = $conversation->messages()->doesntExist();

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $request->content,
        ]);

        if ($isFirstMessage) {
            $conversation->character()->increment('chatters_count');
        }

        $history = $conversation->messages()->orderBy('created_at')->get();

        $aiText = app(OpenRouterService::class)->sendMessage(
            $conversation->character,
            $history,
            $user->name,
            $user->gender
        );

        // Split into sentences, then decide how many bubbles based on total length.
        $text      = trim($aiText);
        $totalLen  = mb_strlen($text);
        $sentences = preg_split('/[.!?]+\K\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        if (count($sentences) <= 1 || $totalLen < 120) {
            // Short or single-sentence reply — one bubble.
            $parts = [$text];
        } else {
            // Longer replies: cap at 2 bubbles up to 300 chars, 3 bubbles beyond that.
            $max = $totalLen > 300 ? 3 : 2;
            if (count($sentences) <= $max) {
                $parts = $sentences;
            } else {
                // Merge overflow sentences into the last bubble.
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

        // Unlimited users don't consume credits.
        if (! $user->hasUnlimited()) {
            $user->decrement('message_credits');
        }

        $conversation->touch();

        $fresh = $user->fresh();

        return response()->json([
            'user_message'      => $userMessage,
            'ai_messages'       => $aiMessages,
            'credits_remaining' => $fresh->message_credits,
            'unlimited_until'   => $fresh->unlimited_until,
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
}
