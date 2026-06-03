<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\UserMemory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractMemoriesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    private const MESSAGE_WINDOW = 20;

    public function __construct(
        private readonly int $conversationId,
    ) {}

    public function handle(): void
    {
        $conversation = Conversation::with(['user', 'character'])->find($this->conversationId);

        if ($conversation === null) {
            return;
        }

        $user      = $conversation->user;
        $character = $conversation->character;

        $recentMessages = $conversation->messages()
            ->where('type', 'text')
            ->orderByDesc('created_at')
            ->limit(self::MESSAGE_WINDOW)
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        if ($recentMessages->isEmpty()) {
            return;
        }

        $transcript = $recentMessages->map(function ($msg) use ($user, $character) {
            $speaker = $msg->role === 'user' ? ($user->name ?? 'User') : $character->name;
            return "{$speaker}: {$msg->content}";
        })->implode("\n");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openrouter.key'),
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => config('app.name'),
        ])
        ->timeout(60)
        ->post(rtrim(config('services.openrouter.endpoint'), '/') . '/completions', [
            'model'    => config('services.openrouter.memory_model'),
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt($user->name ?? 'the user', $character->name)],
                ['role' => 'user',   'content' => "Transcript:\n{$transcript}\n\nReturn ONLY valid JSON — no markdown, no code fences."],
            ],
        ]);

        if ($response->failed()) {
            Log::error('ExtractMemoriesJob: OpenRouter request failed', [
                'conversation_id' => $this->conversationId,
                'status'          => $response->status(),
            ]);
            throw new \RuntimeException('OpenRouter API error: ' . $response->status());
        }

        $memories = $this->parseMemories($response->json('choices.0.message.content', ''));

        foreach ($memories as $item) {
            if (! $this->isValid($item)) {
                continue;
            }

            $value    = trim((string) $item['value']);
            $category = trim((string) $item['category']);
            $key      = (isset($item['key']) && $item['key'] !== '' && $item['key'] !== null)
                            ? trim((string) $item['key'])
                            : null;

            // Map "scope" field from extraction prompt to character_id.
            // "global"    → null        (every character can see it)
            // "character" → character id (intimate detail for this character only)
            $characterId = (($item['scope'] ?? 'character') === 'global')
                ? null
                : $character->id;

            if ($key !== null) {
                // Keyed fact — overwrite if already stored for this user + scope + key.
                $existing = UserMemory::where('user_id', $user->id)
                    ->where(function ($q) use ($characterId) {
                        $characterId === null
                            ? $q->whereNull('character_id')
                            : $q->where('character_id', $characterId);
                    })
                    ->where('key', $key)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'value'                  => $value,
                        'category'               => $category,
                        'source_conversation_id' => $this->conversationId,
                    ]);
                } else {
                    UserMemory::create([
                        'user_id'                => $user->id,
                        'character_id'           => $characterId,
                        'key'                    => $key,
                        'value'                  => $value,
                        'category'               => $category,
                        'source_conversation_id' => $this->conversationId,
                    ]);
                }
            } else {
                // Accumulating event — skip exact-value duplicates in the same scope.
                $exists = UserMemory::where('user_id', $user->id)
                    ->where(function ($q) use ($characterId) {
                        $characterId === null
                            ? $q->whereNull('character_id')
                            : $q->where('character_id', $characterId);
                    })
                    ->whereNull('key')
                    ->where('value', $value)
                    ->exists();

                if (! $exists) {
                    UserMemory::create([
                        'user_id'                => $user->id,
                        'character_id'           => $characterId,
                        'key'                    => null,
                        'value'                  => $value,
                        'category'               => $category,
                        'source_conversation_id' => $this->conversationId,
                    ]);
                }
            }
        }
    }

    private function systemPrompt(string $userName, string $characterName): string
    {
        return <<<PROMPT
You are a memory extraction engine for an AI companion app. Read the transcript and extract factual information about the HUMAN USER ({$userName}), not about the AI character ({$characterName}).

Extract only facts that are:
- Explicitly stated by the user (not implied or assumed)
- Useful for personalising future conversations
- About the user personally (not general questions or pleasantries)

For each fact, output a JSON object in the "memories" array:
- "key": snake_case slug for overwritable facts ("job", "city", "age", "name"). Use null for one-off events that should accumulate.
- "value": one concise third-person sentence. E.g. "The user works as a nurse in Jakarta." or "The user mentioned feeling lonely this evening."
- "category": exactly one of: identity | preference | relationship | life_event | boundary
- "scope": "global" for broad facts (name, job, city, age) or "character" for intimate details specific to this relationship

Output ONLY this JSON — no markdown, no code fences, no extra text:
{"memories": [
  {"key": "job", "value": "The user works as a nurse.", "category": "identity", "scope": "global"},
  {"key": null, "value": "The user mentioned feeling homesick today.", "category": "life_event", "scope": "character"}
]}

If nothing notable was said, return: {"memories": []}
PROMPT;
    }

    private function parseMemories(string $raw): array
    {
        $raw = trim($raw);

        // Strip markdown code fences.
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        $raw = trim($raw);

        // If prose leaked in, extract the first {...} block.
        if (! str_starts_with($raw, '{')) {
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $raw = $m[0];
            } else {
                Log::warning('ExtractMemoriesJob: no JSON in LLM response', [
                    'conversation_id' => $this->conversationId,
                    'raw'             => mb_substr($raw, 0, 500),
                ]);
                return [];
            }
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['memories']) || ! is_array($decoded['memories'])) {
            Log::warning('ExtractMemoriesJob: unexpected JSON structure', [
                'conversation_id' => $this->conversationId,
                'raw'             => mb_substr($raw, 0, 500),
            ]);
            return [];
        }

        return $decoded['memories'];
    }

    private function isValid(mixed $item): bool
    {
        $validCategories = ['identity', 'preference', 'relationship', 'life_event', 'boundary'];

        return is_array($item)
            && isset($item['value'], $item['category'])
            && is_string($item['value'])
            && trim($item['value']) !== ''
            && in_array($item['category'], $validCategories, true);
    }
}
