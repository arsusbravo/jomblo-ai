<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

        $body = "New support message from {$user->name} (#{$user->id}).\n\nReply here: {$adminUrl}";

        Mail::send([], [], fn ($mail) => $mail
            ->to($adminEmail)
            ->replyTo($user->email, $user->name)
            ->subject("[JombloAI] New message from {$user->name}")
            ->text('emails.plain', ['body' => $body])
            ->withSymfonyMessage(function ($msg) {
                $msg->getHeaders()->addTextHeader('X-Mailin-Track-Click', 'false');
                $msg->getHeaders()->addTextHeader('X-Mailin-Track-Open', 'false');
            })
        );

        return response()->json(['message' => $msg], 201);
    }
}
