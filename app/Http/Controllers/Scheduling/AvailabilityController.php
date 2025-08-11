<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    /**
     * Show availability for the currently active identity.
     */
    public function getMyAvailability(Request $request): JsonResponse
    {
        $identity = $this->resolveIdentity($request);
        abort_unless($identity, 403, __('errors.no_active_identity'));

        $availability = AvailabilityRule::where('identity_id', $identity->id)->get();

        return response()->json(['data' => $availability]);
    }

    /**
     * Update availability for the currently active identity.
     */
    public function updateMyAvailability(Request $request): JsonResponse
    {
        $identity = $this->resolveIdentity($request);
        abort_unless($identity, 403, __('errors.no_active_identity'));

        $validator = Validator::make($request->all(), [
            'availability' => 'required|array',
            'availability.*.day_of_week'  => 'required|integer|between:0,6',
            'availability.*.start_time'   => 'required|date_format:H:i',
            'availability.*.end_time'     => 'required|date_format:H:i',
            'availability.*.booking_type' => 'required|string',
            'availability.*.is_active'    => 'sometimes|boolean',
        ]);

        $validator->after(function ($v) use ($request) {
            foreach ($request->availability as $i => $rule) {
                if (isset($rule['start_time'], $rule['end_time']) && $rule['end_time'] <= $rule['start_time']) {
                    $v->errors()->add("availability.$i.end_time", __('validation.end_after_start'));
                }
            }
        });

        $validator->validate();

        AvailabilityRule::where('identity_id', $identity->id)->delete();

        foreach ($request->availability as $rule) {
            AvailabilityRule::create([
                'identity_id'  => $identity->id,
                'day_of_week'  => (int)$rule['day_of_week'],
                'start_time'   => $rule['start_time'],
                'end_time'     => $rule['end_time'],
                'booking_type' => $rule['booking_type'],
                'is_active'    => array_key_exists('is_active', $rule) ? (bool)$rule['is_active'] : true,
            ]);
        }

        return response()->json(['data' => ['message' => __('availability.updated')]]);
    }

    /**
     * Get availability for a specific identity (public-facing).
     */
    public function getUserAvailability(User $userOrLegacy, Request $request): JsonResponse
    {
        $identityId = $request->route('identity') ?? null;
        abort_unless($identityId, 404);

        $availability = AvailabilityRule::where('identity_id', $identityId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $availability
        ]);
    }

    public function getIdentityAvailability(\App\Models\Identity $identity): \Illuminate\Http\JsonResponse
    {
        $availability = \App\Models\AvailabilityRule::where('identity_id', $identity->id)
            ->where('is_active', true)
            ->get();

        return response()->json(['data' => $availability]);
    }

    /**
     * Fallback: header-based currentIdentity() OR user's active_identity_id.
     */
    private function resolveIdentity(Request $request): ?Identity
    {
        $identity = $request->currentIdentity();
        if ($identity instanceof Identity) {
            return $identity;
        }

        $activeId = $request->user()?->active_identity_id;
        return $activeId ? Identity::find($activeId) : null;
    }
}
