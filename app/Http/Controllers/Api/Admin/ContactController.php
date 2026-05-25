<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;

class ContactController extends Controller
{
    /** List of users who have sent support messages, newest first. */
    public function index(): JsonResponse
    {
        $users = User::whereHas('supportMessages')
            ->withCount([
                'supportMessages as unread_count' => fn ($q) => $q
                    ->where('sender', 'user')
                    ->whereNull('read_at'),
            ])
            ->get();

        // Get the last message per user using MAX(id) — compatible with old MariaDB (no window functions).
        $lastMessages = SupportMessage::whereIn('user_id', $users->pluck('id'))
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('support_messages')->groupBy('user_id');
            })
            ->get()
            ->keyBy('user_id');

        $threads = $users
            ->map(fn ($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'unread_count' => $u->unread_count,
                'last_message' => $lastMessages[$u->id]?->message ?? '',
                'last_at'      => $lastMessages[$u->id]?->created_at,
            ])
            ->sortByDesc('last_at')
            ->values();

        return response()->json(['threads' => $threads]);
    }

    /** Full conversation with one user; marks their messages as read. */
    public function conversation(User $user): JsonResponse
    {
        $messages = SupportMessage::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        // Mark user messages as read (admin has seen them).
        SupportMessage::where('user_id', $user->id)
            ->where('sender', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'user'     => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'messages' => $messages,
        ]);
    }

    /** Admin posts a reply. */
    public function reply(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:3000'],
        ]);

        $msg = SupportMessage::create([
            'user_id' => $user->id,
            'sender'  => 'admin',
            'message' => $request->message,
        ]);

        // Notify user: just a link, not the content.
        $contactUrl = rtrim(config('app.url'), '/') . '/dashboard/contact';

        $body = implode("\n\n", [
            "Hi {$user->name},",
            "The JombloAI support team has replied to your message.",
            "Click the link below to read the reply and continue the conversation:",
            $contactUrl,
            "If you have any further questions, feel free to send another message through the contact page. We are always happy to help and will get back to you as soon as possible.",
            "Thank you for using JombloAI.",
            "---",
            "Best regards,",
            "JombloAI Support Team",
            "This is an automated notification. Please do not reply directly to this email — use the link above to continue the conversation.",
        ]);

        BrevoMailer::sendText(
            toEmail: $user->email,
            toName:  $user->name,
            subject: 'You have a new reply — JombloAI Support',
            body:    $body,
        );

        return response()->json(['message' => $msg], 201);
    }

    /** Total unread user messages (for admin badge). */
    public function unreadCount(): JsonResponse
    {
        $count = SupportMessage::where('sender', 'user')->whereNull('read_at')->count();
        return response()->json(['unread' => $count]);
    }
}
