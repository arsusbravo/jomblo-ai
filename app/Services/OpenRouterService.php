<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterService
{
    private string $endpoint;
    private string $key;
    private string $model;

    public function __construct()
    {
        $this->key = config('services.openrouter.key');
        $this->model = config('services.openrouter.model');
        $this->endpoint = rtrim(config('services.openrouter.endpoint'), '/') . '/completions';
    }

    public function sendMessage(Character $character, Collection $history, ?string $userName = null): string
    {
        $systemPrompt = strtr($character->personality_prompt, [
            '{user_name}' => trim((string) $userName) !== '' ? trim($userName) : 'you',
        ]);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->key,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->post($this->endpoint, [
            'model' => $this->model,
            'messages' => $messages,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenRouter API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}
