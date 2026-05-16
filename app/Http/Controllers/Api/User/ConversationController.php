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

        return response()->json(['conversation' => $conversation]);
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

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $request->content,
        ]);

        $history = $conversation->messages()->orderBy('created_at')->get();

        $aiText = app(OpenRouterService::class)->sendMessage(
            $conversation->character,
            $history
        );

        $aiMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $aiText,
        ]);

        // Unlimited users don't consume credits.
        if (! $user->hasUnlimited()) {
            $user->decrement('message_credits');
        }

        $conversation->touch();

        $fresh = $user->fresh();

        return response()->json([
            'user_message'      => $userMessage,
            'ai_message'        => $aiMessage,
            'credits_remaining' => $fresh->message_credits,
            'unlimited_until'   => $fresh->unlimited_until,
        ]);
    }
}
