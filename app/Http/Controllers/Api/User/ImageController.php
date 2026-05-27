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
    private const POSE_MAP_REALISTIC = [
        'seductive' => 'confident flirty gaze, charming smile, close-up portrait, warm soft light',
        'lingerie'  => 'wearing lingerie, elegant pin-up style, soft warm light, tasteful',
        'bikini'    => 'wearing a bikini, relaxed summer pose, beach vibes, natural light',
        'lying'     => 'lounging on a bed, relaxed pose, soft warm light, looking at camera',
        'backpose'  => 'looking back over shoulder, graceful back pose, elegant, soft light',
        'boudoir'   => 'sitting on bed, elegant portrait, warm soft light, tasteful pin-up',
    ];

    private const POSE_MAP_ANIME = [
        'selfie'   => 'close-up selfie, smiling at camera, cute expression',
        'winking'  => 'playful wink, flirty smile, kawaii',
        'shy'      => 'shy smile, blushing, looking slightly down',
        'action'   => 'dynamic action pose, wind blowing hair, confident',
        'sitting'  => 'sitting pose, legs crossed, cheerful',
        'portrait' => 'close-up portrait, soft dreamy eyes, gentle smile',
    ];

    public function generate(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $imageCost     = config('pricing.image_credits', 5);
        $freeRemaining = $user->freeImagesRemainingThisPeriod(); // null = not unlimited
        $isFree        = $freeRemaining !== null && $freeRemaining > 0;

        if (! $isFree && $user->message_credits < $imageCost) {
            return response()->json([
                'message'              => 'out_of_credits',
                'credits_remaining'    => $user->message_credits,
                'free_images_remaining' => $freeRemaining,
            ], 402);
        }

        $character = $conversation->character;
        $isAnime   = $character->category === 'anime';
        $poseMap   = $isAnime ? self::POSE_MAP_ANIME : self::POSE_MAP_REALISTIC;
        $validKeys = implode(',', array_keys($poseMap));

        $request->validate([
            'pose' => ['required', 'string', 'in:' . $validKeys],
        ]);

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

        $poseText = $poseMap[$request->pose];

        $bodyShape = 'curvy figure, full body proportions matching reference, same physique as reference image, voluptuous, thick thighs, full hips, maintaining original body shape';

        $prompt = $isAnime
            ? "{$poseText}, {$bodyShape}, same outfit as reference image, original clothing preserved, anime illustration, detailed, vibrant colors, high quality"
            : "{$poseText}, {$bodyShape}, tasteful, elegant, photorealistic, high quality, 4k, professional photography";

        $negative = $isAnime
            ? 'skinny, slim, thin, underweight, bony, petite figure, different outfit, outfit change, costume change, nude, naked, exposed genitals, explicit, pornographic, photograph, realistic, blurry, bad quality, watermark'
            : 'skinny, slim, thin, underweight, bony, petite figure, nude, naked, exposed genitals, explicit, pornographic, cartoon, blurry, bad quality, watermark';

        try {
            $imageUrl = FalService::generateImage($imageRef, $prompt, $negative);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'content_filtered') {
                return response()->json(['message' => 'generation_failed'], 500);
            }

            // Retry once with a simpler, safer prompt before giving up.
            $fallbackPrompt = $isAnime
                ? "portrait pose, same outfit as reference image, anime illustration, vibrant colors, high quality"
                : "elegant portrait, soft light, photorealistic, high quality, 4k";
            $fallbackNegative = 'nude, naked, explicit, blurry, bad quality, watermark';

            try {
                $imageUrl = FalService::generateImage($imageRef, $fallbackPrompt, $fallbackNegative);
            } catch (\RuntimeException) {
                return response()->json(['message' => 'content_filtered'], 422);
            }
        }

        $message = $conversation->messages()->create([
            'role'    => 'assistant',
            'type'    => 'image',
            'content' => $imageUrl,
            'meta'    => ['pose' => $request->pose],
        ]);

        if (! $isFree) {
            $user->decrement('message_credits', $imageCost);
        }

        $conversation->touch();

        return response()->json([
            'image_message'          => $message,
            'credits_remaining'      => $user->fresh()->message_credits,
            'free_images_remaining'  => $user->freeImagesRemainingThisPeriod(),
        ], 201);
    }
}
