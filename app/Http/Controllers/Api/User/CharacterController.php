<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    public function index(): JsonResponse
    {
        $characters = Character::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['characters' => $characters]);
    }
}
