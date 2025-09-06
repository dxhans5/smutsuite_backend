<?php

namespace App\Http\Controllers;

use App\Events\AvailabilityUpdated;
use App\Http\Resources\AvailabilityRuleResource;
use App\Models\AvailabilityRule;
use App\Models\Identity;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AvailabilityController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $identity = Identity::where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $availabilityRules = AvailabilityRule::where('identity_id', $identity->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => AvailabilityRuleResource::collection($availabilityRules)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'booking_type' => 'required|string|in:consultation,session,event',
            'is_available' => 'boolean'
        ]);

        $identity = Identity::where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $availabilityRule = AvailabilityRule::create([
            'identity_id' => $identity->id,
            ...$validated
        ]);

        // Broadcast availability update
        event(new AvailabilityUpdated($identity, $availabilityRule, 'schedule_changed'));

        return response()->json([
            'data' => new AvailabilityRuleResource($availabilityRule)
        ], 201);
    }

    public function update(Request $request, AvailabilityRule $availabilityRule): JsonResponse
    {
        $this->authorize('update', $availabilityRule);

        $validated = $request->validate([
            'day_of_week' => 'integer|between:0,6',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after:start_time',
            'booking_type' => 'string|in:consultation,session,event',
            'is_available' => 'boolean'
        ]);

        $availabilityRule->update($validated);

        // Broadcast availability update
        event(new AvailabilityUpdated($availabilityRule->identity, $availabilityRule, 'schedule_changed'));

        return response()->json([
            'data' => new AvailabilityRuleResource($availabilityRule)
        ]);
    }

    public function destroy(AvailabilityRule $availabilityRule): JsonResponse
    {
        $this->authorize('delete', $availabilityRule);

        $identity = $availabilityRule->identity;
        $availabilityRule->delete();

        // Broadcast availability update
        event(new AvailabilityUpdated($identity, null, 'schedule_changed'));

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:online,offline,busy,available'
        ]);

        $identity = Identity::where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $updateType = match($validated['status']) {
            'online', 'available' => 'went_online',
            'offline' => 'went_offline',
            'busy' => 'status_changed'
        };

        // Broadcast status change
        event(new AvailabilityUpdated($identity, null, $updateType));

        return response()->json([
            'data' => [
                'identity_id' => $identity->id,
                'status' => $validated['status'],
                'updated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get the authenticated user's availability rules
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Get active identity
        $activeIdentity = null;
        if ($user->active_identity_id) {
            $activeIdentity = $user->identities()->where('id', $user->active_identity_id)->first();
        }
        if (!$activeIdentity) {
            $activeIdentity = $user->identities()->where('is_active', true)->first();
        }

        if (!$activeIdentity) {
            return response()->json([
                'message' => __('availability.no_active_identity')
            ], 422);
        }

        $availabilityRules = $activeIdentity->availabilityRules()->get();

        return response()->json([
            'data' => $availabilityRules->map(function($rule) {
                return [
                    'id' => $rule->id,
                    'identity_id' => $rule->identity_id,
                    'day_of_week' => $rule->day_of_week,
                    'start_time' => $rule->start_time,
                    'end_time' => $rule->end_time,
                    'booking_type' => $rule->booking_type,
                    'is_available' => $rule->is_available,
                    'created_at' => $rule->created_at->toISOString(),
                    'updated_at' => $rule->updated_at->toISOString(),
                ];
            }),
            'meta' => [
                'success' => true,
                'message' => __('availability.fetched')
            ]
        ]);
    }

    /**
     * Update the authenticated user's availability settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMyAvailability(Request $request)
    {
        $user = $request->user();

        // Get active identity - try user's active_identity_id first, then fallback to is_active flag
        $activeIdentity = null;
        if ($user->active_identity_id) {
            $activeIdentity = $user->identities()->where('id', $user->active_identity_id)->first();
        }
        if (!$activeIdentity) {
            $activeIdentity = $user->identities()->where('is_active', true)->first();
        }

        if (!$activeIdentity) {
            return response()->json([
                'message' => __('availability.no_active_identity')
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:online,offline,busy,away',
            'availability' => 'sometimes|array',
            'availability.*.day_of_week' => 'required_with:availability|integer|between:0,6',
            'availability.*.start_time' => 'required_with:availability|date_format:H:i',
            'availability.*.end_time' => 'required_with:availability|date_format:H:i|after:availability.*.start_time',
            'availability.*.booking_type' => 'sometimes|string',
            'availability.*.is_available' => 'sometimes|boolean',
        ]);

        // Update status if provided
        if (isset($validated['status'])) {
            $activeIdentity->update(['status' => $validated['status']]);
        }

        // Update availability rules if provided
        if (isset($validated['availability']) && is_array($validated['availability'])) {
            // Remove existing rules for this identity
            $activeIdentity->availabilityRules()->delete();

            // Create new rules
            foreach ($validated['availability'] as $ruleData) {
                $activeIdentity->availabilityRules()->create([
                    'day_of_week' => $ruleData['day_of_week'],
                    'start_time' => $ruleData['start_time'],
                    'end_time' => $ruleData['end_time'],
                    'booking_type' => $ruleData['booking_type'] ?? 'chat',
                    'is_available' => $ruleData['is_available'] ?? true,
                ]);
            }
        }

        // Broadcast the availability update
        if (class_exists('App\Events\AvailabilityUpdated')) {
            event(new \App\Events\AvailabilityUpdated($activeIdentity, null, 'bulk_update'));
        }

        return response()->json([
            'data' => [
                'message' => __('availability.updated_successfully'),
                'identity_id' => $activeIdentity->id,
                'status' => $activeIdentity->status ?? 'offline',
                'updated_at' => $activeIdentity->fresh()->updated_at->toISOString(),
            ],
            'meta' => [
                'success' => true,
                'message' => __('availability.updated_successfully')
            ]
        ]);
    }

    public function showByIdentity(Request $request, Identity $identity): JsonResponse
    {
        $availabilityRules = AvailabilityRule::where('identity_id', $identity->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => AvailabilityRuleResource::collection($availabilityRules),
            'meta' => [
                'success' => true,
                'message' => __('availability.retrieved_successfully')
            ]
        ]);
    }
}
