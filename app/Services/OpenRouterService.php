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

    public function sendMessage(Character $character, Collection $history, ?string $userName = null, ?string $userGender = null): string
    {
        $name = trim((string) $userName) !== '' ? trim($userName) : 'you';
        $userGender = in_array($userGender, ['male', 'female'], true) ? $userGender : null;

        $userRole = match ($userGender) {
            'male'   => 'boyfriend',
            'female' => 'girlfriend',
            default  => 'partner',
        };

        $systemPrompt = strtr($character->personality_prompt, [
            '{user_name}'   => $name,
            '{user_gender}' => $userGender ?? '',
            '{user_role}'   => $userRole,
        ]);

        $systemPrompt .= "\n\n" . $this->orientationClause($character->gender, $userGender, $name);

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

    /**
     * Authoritative relationship/orientation instruction appended after the
     * admin prompt. It deliberately overrides any conflicting gendered wording
     * (e.g. "your boyfriend") so the romance works for whoever is chatting.
     */
    private function orientationClause(string $characterGender, ?string $userGender, string $name): string
    {
        $tone = 'Keep all affection romantic, warm, and tasteful — never sexually explicit.';

        if ($userGender === null) {
            return "(Important — this overrides anything above: You are romantically and "
                . "affectionately interested in {$name}. {$tone})";
        }

        $sameSex = $characterGender === $userGender;
        $youAre  = $characterGender === 'female' ? 'a woman' : 'a man';
        $theyAre = $userGender === 'female' ? 'a woman' : 'a man';

        if ($sameSex) {
            $orientation = $characterGender === 'female'
                ? "You are {$youAre} who is romantically and emotionally attracted to women (lesbian)."
                : "You are {$youAre} who is romantically and emotionally attracted to men (gay).";
        } else {
            $orientation = "You are {$youAre} who is romantically and emotionally attracted to "
                . ($userGender === 'female' ? 'women' : 'men') . ' (heterosexual).';
        }

        return "(Important — this overrides any conflicting wording above: {$orientation} "
            . "{$name} is {$theyAre}, and {$name} is the person you are romantically interested in. "
            . "Refer to your relationship accordingly. {$tone})";
    }
}
