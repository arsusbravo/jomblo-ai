<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FalService
{
    public static function generateImage(
        string $faceImageUrl,
        string $prompt,
        string $negativePrompt,
    ): string {
        $response = Http::withHeaders([
            'Authorization' => 'Key ' . config('services.fal.key'),
            'Content-Type'  => 'application/json',
        ])->timeout(120)->post('https://fal.run/fal-ai/flux-pulid', [
            'reference_image_url'   => $faceImageUrl,
            'prompt'                => $prompt,
            'negative_prompt'       => $negativePrompt,
            'num_inference_steps'   => 28,
            'guidance_scale'        => 3.5,
            'id_strength'           => 0.95,
            'start_step'            => 0,
            'image_size'            => 'portrait_4_3',
            'enable_safety_checker' => false,
        ]);

        if ($response->failed()) {
            Log::error('fal.ai image generation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Image generation failed.');
        }

        // Detect blacked-out images: fal.ai returns has_nsfw_concepts=true and blacks the image.
        if ($response->json('has_nsfw_concepts.0') === true) {
            throw new \RuntimeException('content_filtered');
        }

        return $response->json('images.0.url');
    }
}
