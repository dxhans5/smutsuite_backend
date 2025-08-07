<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePrivateProfileRequest;
use App\Http\Requests\UpdatePublicProfileRequest;
use App\Models\AvailabilityRule;
use App\Models\BookingRequest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Permission;
use App\Models\PrivateProfile;
use App\Models\PublicProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Notifications\GenericNotification;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = User::with(
            'roles.permissions',
            'permissions',
            'activeIdentity',
            'identities',
        )->find($request->user()->id);
        $user->setRelation('all_permissions', $user->all_permissions);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function attachRole(Request $request, User $user, Role $role): JsonResponse
    {
        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_attach_success'),
        ]);
    }

    public function detachRole(User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_detach_success'),
        ]);
    }

    public function attachPermission(User $user, Permission $permission): JsonResponse
    {
        if ($user->permissions->contains($permission)) {
            return response()->json([
                'success' => false,
                'message' => __('permissionsroles.permission_already_attached'),
            ], 409);
        }

        $user->permissions()->attach($permission);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.permission_attach_success'),
        ]);
    }

    public function detachPermission(User $user, Permission $permission): JsonResponse
    {
        if (! $user->permissions->contains($permission)) {
            return response()->json([
                'success' => false,
                'message' => __('permissionsroles.permission_not_attached'),
            ], 404);
        }

        $user->permissions()->detach($permission);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.permission_detach_success'),
        ]);
    }

    public function assignRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        if (! $request->user()) {
            abort(401);
        }

        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->syncWithoutDetaching($validated['roles']);
        }

        if (!empty($validated['permissions'])) {
            $user->permissions()->syncWithoutDetaching($validated['permissions']);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_assign_success'),
        ]);
    }

    public function removeRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->detach($validated['roles']);
        }

        if (!empty($validated['permissions'])) {
            $user->permissions()->detach($validated['permissions']);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_remove_success'),
        ]);
    }

    public function getMyProfiles(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $public  = \App\Models\PublicProfile::where('identity_id', $identity->id)->first();
        $private = \App\Models\PrivateProfile::where('identity_id', $identity->id)->first();

        return response()->json([
            'public_profile'  => $public,
            'private_profile' => $private,
        ]);
    }

    public function updatePublicProfile(UpdatePublicProfileRequest $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $profile = PublicProfile::firstOrNew(['identity_id' => $identity->id]);

        $this->authorize('updatePublic', $profile);

        $profile->fill($request->validated())->save();

        return response()->json($profile);
    }

    public function updatePrivateProfile(UpdatePrivateProfileRequest $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $profile = PrivateProfile::firstOrNew(['identity_id' => $identity->id]);

        $this->authorize('updatePrivate', $profile);

        $profile->fill($request->validated())->save();

        return response()->json($profile);
    }

    public function getPublicProfile($identityId, Request $request): JsonResponse
    {
        $viewer = $request->user();
        $profile = \App\Models\PublicProfile::where('identity_id', $identityId)->firstOrFail();

        if (!$profile->is_visible) {
            $owns = $viewer && \App\Models\Identity::forUser($viewer->id)->where('id', $identityId)->exists();
            abort_unless($owns, 404);
        }

        return response()->json($profile);
    }

    public function getMyAvailability(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        return response()->json(
            \App\Models\AvailabilityRule::where('identity_id', $identity->id)->get()
        );
    }

    public function updateMyAvailability(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $request->validate([
            'availability' => 'required|array',
            'availability.*.day_of_week' => 'required|integer|between:0,6',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i|after:availability.*.start_time',
            'availability.*.booking_type' => 'required|string',
        ]);

        \App\Models\AvailabilityRule::where('identity_id', $identity->id)->delete();

        foreach ($request->availability as $rule) {
            \App\Models\AvailabilityRule::create([
                'identity_id' => $identity->id,
                ...$rule,
            ]);
        }

        return response()->json(['message' => __('Availability updated.')]);
    }

    public function getUserAvailability(\App\Models\User $userOrLegacy, Request $request): JsonResponse
    {
        // Prefer: route-model bind an Identity instead of User
        $identityId = $request->route('identity') ?? null;
        abort_unless($identityId, 404);

        return response()->json(
            \App\Models\AvailabilityRule::where('identity_id', $identityId)
                ->where('is_active', true)->get()
        );
    }

    public function createBookingRequest(Request $request): JsonResponse
    {
        $request->validate([
            'creator_identity_id' => 'required|uuid|exists:identities,id',
            'requested_at' => 'required|date|after:now',
            'booking_type' => 'required|string',
            'notes' => 'nullable|string',
            'timezone' => 'nullable|string',
        ]);

        $clientIdentity = $request->currentIdentity();
        abort_unless($clientIdentity, 403, 'No active identity selected.');

        $booking = \App\Models\BookingRequest::create([
            'creator_identity_id' => $request->creator_identity_id,
            'client_identity_id'  => $clientIdentity->id,
            'requested_at' => $request->requested_at,
            'booking_type' => $request->booking_type,
            'status' => 'pending',
            'notes' => $request->notes,
            'timezone' => $request->timezone,
        ]);

        return response()->json($booking, 201);
    }

    public function getMyBookings(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        return response()->json([
            'as_creator' => \App\Models\BookingRequest::where('creator_identity_id', $identity->id)->get(),
            'as_client'  => \App\Models\BookingRequest::where('client_identity_id', $identity->id)->get(),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_identity_id' => 'required|uuid|exists:identities,id',
            'body' => 'required|string|min:1|max:2000',
        ]);

        $sender = $request->currentIdentity();
        abort_unless($sender, 403, 'No active identity selected.');
        abort_if($request->recipient_identity_id === $sender->id, 422, 'Cannot message yourself.');

        // Find or create a thread between these two identities
        $thread = \App\Models\MessageThread::whereHas('participants', function ($q) use ($sender) {
            $q->where('identity_id', $sender->id);
        })->whereHas('participants', function ($q) use ($request) {
            $q->where('identity_id', $request->recipient_identity_id);
        })->first();

        if (!$thread) {
            $thread = \App\Models\MessageThread::create();
            $thread->participants()->attach([$sender->id, $request->recipient_identity_id], [], 'participants'); // pivot table uses identity_id now
        }

        $message = $thread->messages()->create([
            'sender_identity_id' => $sender->id,
            'body' => $request->body,
        ]);

        return response()->json($message, 201);
    }

    public function getThreads(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $threads = \App\Models\MessageThread::whereHas('participants', function ($q) use ($identity) {
            $q->where('identity_id', $identity->id);
        })
            ->with([
                'participants.identity:id,alias,role,is_active',
                // If you often show preview/snippet, load latest message only
                'messages' => fn($q) => $q->latest()->limit(1),
            ])
            ->latest()
            ->get();

        return response()->json($threads);
    }

    public function getThreadMessages($id, Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $thread = \App\Models\MessageThread::with('messages.senderIdentity')
            ->whereHas('participants', fn ($q) => $q->where('identity_id', $identity->id))
            ->findOrFail($id);

        return response()->json($thread->messages()->latest()->get());
    }

    public function markAsRead($id, Request $request): JsonResponse
    {
        DB::table('message_thread_user')
            ->where('message_thread_id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function deleteMessage($id, Request $request): JsonResponse
    {
        $message = Message::where('id', $id)
            ->where('sender_id', $request->user()->id)
            ->firstOrFail();

        $message->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function notify(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string'],
            'action_url' => ['nullable', 'url'],
            'type' => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->user_id);

        $user->notify(new GenericNotification([
            'message' => $request->message,
            'action_url' => $request->action_url,
            'type' => $request->type,
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
