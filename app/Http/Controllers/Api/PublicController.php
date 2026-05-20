<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function home(): JsonResponse
    {
        return response()->json([
            'message' => 'Welcome to JombloAI',
            'description' => 'Your AI-powered platform.',
        ]);
    }

    public function about(): JsonResponse
    {
        return response()->json([
            'title' => 'About JombloAI',
            'content' => 'JombloAI is an AI-powered platform built with Laravel and Vue.js.',
        ]);
    }

    public function featuredCharacters(): JsonResponse
    {
        $pick = fn(string $gender) => Character::where('is_active', true)
            ->where('gender', $gender)
            ->whereNotNull('avatar_path')
            ->orderByDesc('chatters_count')
            ->inRandomOrder()
            ->limit(4)
            ->get(['id', 'name', 'gender', 'avatar_path'])
            ->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'gender'     => $c->gender,
                'avatar_url' => $c->avatar_url,
            ]);

        return response()->json([
            'female' => $pick('female'),
            'male'   => $pick('male'),
        ]);
    }

    public function pricing(): JsonResponse
    {
        return response()->json([
            'currency' => config('pricing.currency'),
            'plans'    => config('pricing.plans'),
        ]);
    }
}
