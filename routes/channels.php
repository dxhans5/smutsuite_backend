<?php

use App\Models\BookingRequest;
use App\Models\MessageThread;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('message-thread.{threadId}', function ($user, $threadId) {
    return MessageThread::where('id', $threadId)
        ->whereHas('participants', fn($q) =>
        $q->where('identity_id', $user->active_identity_id)
        )->exists();
});

// Add to routes/channels.php
Broadcast::channel('booking-request.{bookingId}', function ($user, $bookingId) {
    return BookingRequest::where('id', $bookingId)
        ->where(function($query) use ($user) {
            $query->where('creator_identity_id', $user->active_identity_id)
                ->orWhere('client_identity_id', $user->active_identity_id);
        })->exists();
});

Broadcast::channel('creator-bookings.{identityId}', function ($user, $identityId) {
    return $user->active_identity_id === $identityId;
});

Broadcast::channel('client-bookings.{identityId}', function ($user, $identityId) {
    return $user->active_identity_id === $identityId;
});
