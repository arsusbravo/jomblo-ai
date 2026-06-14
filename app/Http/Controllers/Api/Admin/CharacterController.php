<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CharacterController extends Controller
{
    public function index(): JsonResponse
    {
        // chatters_count is now a persistent column on characters (incremented
        // the first time a user sends a message), so no withCount needed.
        $characters = Character::with('translations')->orderBy('name')->get();

        return response()->json(['characters' => $characters]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'category' => ['required', 'in:anime,realistic'],
            'description' => ['required', 'string'],
            'personality_prompt' => ['required', 'string'],
            'avatar' => ['nullable', 'image', 'max:1024'],
            'is_active' => ['boolean'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('characters', 'public');
        }

        $character = Character::create([
            'name' => $request->name,
            'gender' => $request->gender,
            'category' => $request->category,
            'description' => $request->description,
            'personality_prompt' => $request->personality_prompt,
            'avatar_path' => $avatarPath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['character' => $character], 201);
    }

    public function update(Request $request, Character $character): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'category' => ['required', 'in:anime,realistic'],
            'description' => ['required', 'string'],
            'personality_prompt' => ['required', 'string'],
            'avatar' => ['nullable', 'image', 'max:1024'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($character->avatar_path) {
                Storage::disk('public')->delete($character->avatar_path);
            }
            $character->avatar_path = $request->file('avatar')->store('characters', 'public');
        }

        $character->fill([
            'name' => $request->name,
            'gender' => $request->gender,
            'category' => $request->category,
            'description' => $request->description,
            'personality_prompt' => $request->personality_prompt,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $character->save();

        return response()->json(['character' => $character]);
    }

    public function destroy(Character $character): JsonResponse
    {
        if ($character->avatar_path) {
            Storage::disk('public')->delete($character->avatar_path);
        }

        $character->delete();

        return response()->json(['message' => 'Character deleted.']);
    }

    public function saveTranslation(Request $request, Character $character): JsonResponse
    {
        $request->validate([
            'locale'             => ['required', 'in:en,nl,fr,de'],
            'description'        => ['required', 'string'],
            'personality_prompt' => ['required', 'string'],
        ]);

        $translation = $character->translations()->updateOrCreate(
            ['locale' => $request->locale],
            [
                'description'        => $request->description,
                'personality_prompt' => $request->personality_prompt,
            ]
        );

        return response()->json(['translation' => $translation]);
    }

    public function deleteTranslation(Character $character, string $locale): JsonResponse
    {
        $character->translations()->where('locale', $locale)->delete();

        return response()->json(['message' => 'Translation deleted.']);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $characters = Character::with('translations')->orderBy('name')->get();

        $payload = $characters->map(fn (Character $char) => [
            'name'               => $char->name,
            'gender'             => $char->gender,
            'category'           => $char->category,
            'description'        => $char->description,
            'personality_prompt' => $char->personality_prompt,
            'is_active'          => $char->is_active,
            'avatar_url'         => $char->avatar_url,
            'translations'       => $char->translations->map(fn ($t) => [
                'locale'             => $t->locale,
                'description'        => $t->description,
                'personality_prompt' => $t->personality_prompt,
            ])->values(),
        ]);

        $json     = json_encode(['version' => 1, 'exported_at' => now()->toIso8601String(), 'characters' => $payload], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'characters-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(fn () => print($json), $filename, ['Content-Type' => 'application/json']);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:51200'],
        ]);

        $content = file_get_contents($request->file('file')->path());
        $data    = json_decode($content, true);

        if (! is_array($data) || ! isset($data['characters']) || ! is_array($data['characters'])) {
            return response()->json(['message' => 'Invalid file format.'], 422);
        }

        $imported = 0;
        $skipped  = 0;

        foreach ($data['characters'] as $char) {
            if (empty($char['name']) || empty($char['personality_prompt']) || ! in_array($char['gender'] ?? '', ['male', 'female'])) {
                $skipped++;
                continue;
            }

            $avatarPath = null;
            if (! empty($char['avatar_url'])) {
                try {
                    $contents = file_get_contents($char['avatar_url']);
                    if ($contents !== false) {
                        $ext        = pathinfo(parse_url($char['avatar_url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                        $filename   = 'characters/' . uniqid('import_', true) . '.' . $ext;
                        Storage::disk('public')->put($filename, $contents);
                        $avatarPath = $filename;
                    }
                } catch (\Throwable) {
                    // Avatar download failed — import character without avatar.
                }
            }

            $newCharacter = Character::create([
                'name'               => $char['name'],
                'gender'             => $char['gender'],
                'category'           => in_array($char['category'] ?? '', ['anime', 'realistic']) ? $char['category'] : 'realistic',
                'description'        => $char['description'] ?? '',
                'personality_prompt' => $char['personality_prompt'],
                'is_active'          => $char['is_active'] ?? true,
                'avatar_path'        => $avatarPath,
            ]);

            if (! empty($char['translations']) && is_array($char['translations'])) {
                foreach ($char['translations'] as $t) {
                    if (empty($t['locale']) || ! in_array($t['locale'], ['en', 'nl', 'fr', 'de'])) {
                        continue;
                    }
                    $newCharacter->translations()->updateOrCreate(
                        ['locale' => $t['locale']],
                        [
                            'description'        => $t['description'] ?? '',
                            'personality_prompt' => $t['personality_prompt'] ?? '',
                        ]
                    );
                }
            }

            $imported++;
        }

        return response()->json(['imported' => $imported, 'skipped' => $skipped]);
    }
}
