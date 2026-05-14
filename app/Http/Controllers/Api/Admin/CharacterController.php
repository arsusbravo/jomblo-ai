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
        $characters = Character::orderBy('name')->get();

        return response()->json(['characters' => $characters]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
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
}
