<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;

class ContactController extends Controller
{
    /** User: load their thread and mark admin replies as read. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = SupportMessage::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        // Mark all unread admin messages as read now that the user has seen them.
        SupportMessage::where('user_id', $user->id)
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['messages' => $messages]);
    }

    /** User: send a new message. */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        $user = $request->user();

        $msg = SupportMessage::create([
            'user_id' => $user->id,
            'sender'  => 'user',
            'message' => $request->message,
        ]);

        // Notify admin by email.
        $adminEmail = config('mail.support_address', 'info@arsus.nl');
        $adminUrl   = rtrim(config('app.url'), '/') . '/admin/contact/' . $user->id;

        $body = implode("\n\n", [
            "You have a new support message on JombloAI.",
            "From:    {$user->name} (user #{$user->id})",
            "Email:   {$user->email}",
            "Click the link below to open the conversation and reply:",
            $adminUrl,
            "Please reply as soon as possible so the user gets a good experience.",
            "---",
            "This is an automated notification from JombloAI.",
            "Do not reply directly to this email — use the link above to respond in the admin panel.",
        ]);

        BrevoMailer::sendText(
            toEmail:      $adminEmail,
            toName:       'JombloAI Admin',
            subject:      "[JombloAI] New message from {$user->name}",
            body:         $body,
            replyToEmail: $user->email,
            replyToName:  $user->name,
        );

        return response()->json(['message' => $msg], 201);
    }
}
