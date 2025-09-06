<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Enums\BookingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BookingRequestController extends Controller
{
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = BookingRequest::create([
            'id' => Str::uuid(),
            'creator_identity_id' => $request->creator_identity_id,
            'client_identity_id' => $request->user()->active_identity_id,
            'requested_at' => $request->requested_at,
            'booking_type' => $request->booking_type,
            'status' => BookingStatus::PENDING,
            'notes' => $request->notes,
            'timezone' => $request->timezone,
        ]);

        // Trigger real-time broadcast for new booking
        broadcast(new BookingRequestCreated($booking));

        return response()->json([
            'data' => new BookingRequestResource($booking)
        ], 201);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, BookingRequest $booking): JsonResponse
    {
        $success = $booking->updateStatus($request->status);

        if (!$success) {
            return response()->json([
                'message' => __('bookings.invalid_status_transition'),
                'current_status' => $booking->status->value,
                'requested_status' => $request->status->value,
            ], 422);
        }

        return response()->json([
            'data' => new BookingRequestResource($booking->fresh())
        ]);
    }

    public function index(): JsonResponse
    {
        $identityId = auth()->user()->active_identity_id;

        $bookings = BookingRequest::where(function($query) use ($identityId) {
            $query->where('creator_identity_id', $identityId)
                ->orWhere('client_identity_id', $identityId);
        })
            ->with(['creatorIdentity', 'clientIdentity'])
            ->orderBy('requested_at', 'desc')
            ->get();

        return response()->json([
            'data' => BookingRequestResource::collection($bookings)
        ]);
    }

    public function show(BookingRequest $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return response()->json([
            'data' => new BookingRequestResource($booking)
        ]);
    }
}
