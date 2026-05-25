<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailer
{
    public static function sendText(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        ?string $replyToEmail = null,
        ?string $replyToName  = null,
    ): void {
        $payload = [
            'sender'      => [
                'name'  => config('app.name'),
                'email' => config('mail.from.address'),
            ],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'textContent' => $body,
        ];

        if ($replyToEmail) {
            $payload['replyTo'] = ['email' => $replyToEmail, 'name' => $replyToName ?? $replyToEmail];
        }

        $response = Http::withHeaders([
            'api-key'      => config('services.brevo.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            Log::error('Brevo API mail failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }
}
