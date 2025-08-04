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
        $user = User::with('roles.permissions', 'permissions')->find($request->user()->id);
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
        $user = $request->user()->load(['publicProfile', 'privateProfile']);
        return response()->json([
            'public_profile' => $user->publicProfile,
            'private_profile' => $user->privateProfile,
        ]);
    }

    public function updatePublicProfile(UpdatePublicProfileRequest $request): JsonResponse
    {
        $profile = PublicProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );
        return response()->json($profile);
    }

    public function updatePrivateProfile(UpdatePrivateProfileRequest $request): JsonResponse
    {
        $profile = PrivateProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );
        return response()->json($profile);
    }

    public function getPublicProfile($id): JsonResponse
    {
        $profile = PublicProfile::where('user_id', $id)
            ->where('is_visible', true)
            ->firstOrFail();
        return response()->json($profile);
    }

    public function getMyAvailability(Request $request): JsonResponse
    {
        return response()->json(
            AvailabilityRule::where('user_id', $request->user()->id)->get()
        );
    }

    public function updateMyAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'availability' => 'required|array',
            'availability.*.day_of_week' => 'required|integer|between:0,6',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i|after:availability.*.start_time',
            'availability.*.booking_type' => 'required|string',
        ]);

        AvailabilityRule::where('user_id', $request->user()->id)->delete();

        foreach ($request->availability as $rule) {
            AvailabilityRule::create([
                'user_id' => $request->user()->id,
                ...$rule,
            ]);
        }

        return response()->json(['message' => __('Availability updated.')]);
    }

    public function getUserAvailability(User $user): JsonResponse
    {
        return response()->json(
            AvailabilityRule::where('user_id', $user->id)->where('is_active', true)->get()
        );
    }

    public function createBookingRequest(Request $request): JsonResponse
    {
        $request->validate([
            'creator_id' => 'required|uuid|exists:users,id',
            'requested_at' => 'required|date|after:now',
            'booking_type' => 'required|string',
            'notes' => 'nullable|string',
            'timezone' => 'nullable|string',
        ]);

        $booking = BookingRequest::create([
            'creator_id' => $request->creator_id,
            'client_id' => $request->user()->id,
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
        return response()->json([
            'as_creator' => BookingRequest::where('creator_id', $request->user()->id)->get(),
            'as_client' => BookingRequest::where('client_id', $request->user()->id)->get(),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|uuid|exists:users,id|not_in:' . $request->user()->id,
            'body' => 'required|string|min:1|max:2000',
        ]);

        // Check if thread exists between these two
        $thread = MessageThread::whereHas('participants', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->whereHas('participants', function ($q) use ($request) {
            $q->where('user_id', $request->recipient_id);
        })->first();

        if (!$thread) {
            $thread = MessageThread::create();
            $thread->participants()->attach([$request->user()->id, $request->recipient_id]);
        }

        $message = $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return response()->json($message, 201);
    }

    public function getThreads(Request $request): JsonResponse
    {
        $threads = MessageThread::whereHas('participants', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->with('participants', 'messages')->latest()->get();

        return response()->json($threads);
    }

    public function getThreadMessages($id, Request $request): JsonResponse
    {
        $thread = MessageThread::with('messages.sender')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->id))
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
