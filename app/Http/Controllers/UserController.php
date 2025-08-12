<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePrivateProfileRequest;
use App\Http\Requests\UpdatePublicProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\{
    AvailabilityRule,
    BookingRequest,
    Identity,
    Permission,
    PrivateProfile,
    PublicProfile,
    Role,
    User
};
use App\Notifications\GenericNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Return authenticated user info with roles, permissions, identities.
     */
    public function me(Request $request): JsonResponse
    {
        $user = User::with([
            'roles.permissions',
            'permissions',
            'activeIdentity',
            'identities' => function ($q) {
                $q->where('is_active', true);
            },
        ])->find($request->user()->id);

        $user->setRelation('all_permissions', $user->all_permissions);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    // ────────────────────────────────
    // Bookings
    // ────────────────────────────────

    public function createBookingRequest(Request $request): JsonResponse
    {
        $request->validate([
            'creator_identity_id' => 'required|uuid|exists:identities,id',
            'requested_at'        => 'required|date|after:now',
            'booking_type'        => 'required|string',
            'notes'               => 'nullable|string',
            'timezone'            => 'nullable|string',
        ]);

        $clientIdentity = $request->currentIdentity();
        abort_unless($clientIdentity, 403, 'No active identity selected.');

        $booking = BookingRequest::create([
            'creator_identity_id' => $request->creator_identity_id,
            'client_identity_id'  => $clientIdentity->id,
            'requested_at'        => $request->requested_at,
            'booking_type'        => $request->booking_type,
            'status'              => 'pending',
            'notes'               => $request->notes,
            'timezone'            => $request->timezone,
        ]);

        return response()->json($booking, 201);
    }

    public function getMyBookings(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        return response()->json([
            'as_creator' => BookingRequest::where('creator_identity_id', $identity->id)->get(),
            'as_client'  => BookingRequest::where('client_identity_id', $identity->id)->get(),
        ]);
    }

    // ────────────────────────────────
    // Notifications
    // ────────────────────────────────

    public function notify(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'message'    => ['required', 'string'],
            'action_url' => ['nullable', 'url'],
            'type'       => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->user_id);

        $user->notify(new GenericNotification([
            'message'    => $request->message,
            'action_url' => $request->action_url,
            'type'       => $request->type,
        ]));

        return response()->json(['message' => __('Notification sent.')]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json([
            'notifications' => $request->user()->notifications,
        ]);
    }

    public function markNotificationAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => __('Notification marked as read.')]);
    }
}
