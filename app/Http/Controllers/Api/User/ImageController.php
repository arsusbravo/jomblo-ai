<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\FalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    private const POSE_MAP = [
        'seductive' => 'seductive gaze, bedroom eyes, flirty smile, alluring, close-up portrait',
        'lingerie'  => 'wearing lace lingerie, tasteful boudoir style, elegant and sensual',
        'bikini'    => 'wearing bikini, confident sexy pose, sun-kissed skin',
        'lying'     => 'lying on bed, sensual boudoir pose, looking at camera seductively',
        'backpose'  => 'looking seductively over shoulder, back pose, arched back, alluring',
        'boudoir'   => 'boudoir photography, sitting on bed, sensual lighting, elegant and sexy',
    ];

    public function generate(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $imageCost = config('pricing.image_credits', 5);

        if (! $user->hasUnlimited() && $user->message_credits < $imageCost) {
            return response()->json([
                'message'           => 'out_of_credits',
                'credits_remaining' => $user->message_credits,
            ], 402);
        }

        $request->validate([
            'pose' => ['required', 'string', 'in:seductive,lingerie,bikini,lying,backpose,boudoir'],
        ]);

        $character = $conversation->character;

        if (! $character->avatar_path || ! Storage::disk('public')->exists($character->avatar_path)) {
            return response()->json(['message' => 'No avatar available.'], 422);
        }

        // Local .test domain is unreachable from fal.ai — send as base64 data URL.
        // On production the public storage URL is directly accessible.
        if (app()->environment('local')) {
            $fullPath = Storage::disk('public')->path($character->avatar_path);
            $mime     = mime_content_type($fullPath);
            $imageRef = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        } else {
            $imageRef = $character->avatar_url;
        }

        $poseText = self::POSE_MAP[$request->pose];
        $isAnime  = $character->category === 'anime';

        $prompt = $isAnime
            ? "{$poseText}, tasteful and sexy, anime illustration, detailed, vibrant colors, high quality"
            : "{$poseText}, tasteful and sexy, photorealistic, high quality, 4k, professional photography";

        $negative = $isAnime
            ? 'nude, naked, exposed genitals, explicit, pornographic, photograph, realistic, blurry, bad quality, watermark'
            : 'nude, naked, exposed genitals, explicit, pornographic, cartoon, blurry, bad quality, watermark';

        $imageUrl = FalService::generateImage($imageRef, $prompt, $negative);

        $message = $conversation->messages()->create([
            'role'    => 'assistant',
            'type'    => 'image',
            'content' => $imageUrl,
        ]);

        if (! $user->hasUnlimited()) {
            $user->decrement('message_credits', $imageCost);
        }

        $conversation->touch();

        return response()->json([
            'image_message'     => $message,
            'credits_remaining' => $user->fresh()->message_credits,
        ], 201);
    }
}
