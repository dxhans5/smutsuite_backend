<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Show availability for the currently active identity.
     */
    public function getMyAvailability(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $availability = AvailabilityRule::where('identity_id', $identity->id)->get();

        return response()->json($availability);
    }

    /**
     * Update availability for the currently active identity.
     */
    public function updateMyAvailability(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $request->validate([
            'availability' => 'required|array',
            'availability.*.day_of_week'  => 'required|integer|between:0,6',
            'availability.*.start_time'   => 'required|date_format:H:i',
            'availability.*.end_time'     => 'required|date_format:H:i|after:availability.*.start_time',
            'availability.*.booking_type' => 'required|string',
        ]);

        AvailabilityRule::where('identity_id', $identity->id)->delete();

        foreach ($request->availability as $rule) {
            AvailabilityRule::create([
                'identity_id' => $identity->id,
                ...$rule,
            ]);
        }

        return response()->json(['message' => __('Availability updated.')]);
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

        return response()->json($availability);
    }
}
