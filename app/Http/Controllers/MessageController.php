<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles messaging between users via identity-based threads.
 */
class MessageController extends Controller
{
    /**
     * Send a new message to a recipient identity.
     * If a thread does not exist between the sender and recipient, it is created.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => ['required', 'exists:identities,id'],
            'body'         => ['required', 'string'],
        ]);

        $senderId    = $request->user()->active_identity_id;
        $recipientId = $request->recipient_id;

        // Retrieve or create thread between the two participants
        $thread = MessageThread::whereHas('participants', fn ($q) => $q->where('identity_id', $senderId))
            ->whereHas('participants', fn ($q) => $q->where('identity_id', $recipientId))
            ->first();

        if (!$thread) {
            $thread = MessageThread::create();
            $thread->participants()->attach([$senderId, $recipientId]);
        }

        $message = $thread->messages()->create([
            'sender_identity_id' => $senderId,
            'body'               => $request->body,
        ]);

        // Add real-time broadcasting
        broadcast(new MessageSent($message, $thread->id));

        // Use MessageResource for consistent response format
        return response()->json([
            'data' => new MessageResource($message)
        ], 201);
    }

    /**
     * Fetch all threads involving the current user's active identity.
     *
     * @return JsonResponse
     */
    public function threads(): JsonResponse
    {
        $identityId = auth()->user()->active_identity_id;

        $threads = MessageThread::whereHas('participants', fn ($q) =>
        $q->where('identity_id', $identityId)
        )
            ->with(['latestMessage', 'participants'])
            ->get();

        return response()->json(['data' => $threads]);
    }

    /**
     * Show all messages in a given thread, if the user is a participant.
     *
     * @param  string  $id  Thread ID
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $identityId = auth()->user()->active_identity_id;

        $thread = MessageThread::where('id', $id)
            ->whereHas('participants', fn ($q) => $q->where('identity_id', $identityId))
            ->with(['messages.senderIdentity'])
            ->firstOrFail();

        return response()->json([
            'data' => $thread->messages->map(fn($msg) => new MessageResource($msg))
        ]);
    }

    /**
     * Mark a thread as read by updating the pivot timestamp.
     *
     * @param  string  $id  Thread ID
     * @return JsonResponse
     */
    public function markAsRead(string $id): JsonResponse
    {
        $identityId = auth()->user()->active_identity_id;

        $thread = MessageThread::where('id', $id)
            ->whereHas('participants', fn ($q) => $q->where('identity_id', $identityId))
            ->firstOrFail();

        $thread->participants()->updateExistingPivot($identityId, [
            'last_read_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Soft-delete a message if it was sent by the current identity.
     *
     * @param  string  $id  Message ID
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $identityId = auth()->user()->active_identity_id;

        $message = Message::where('id', $id)
            ->where('sender_identity_id', $identityId)
            ->firstOrFail();

        $message->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
