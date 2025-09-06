<?php

namespace App\Events;

use App\Models\BookingRequest;
use App\Http\Resources\BookingRequestResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRequestStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $previousStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("booking-request.{$this->bookingRequest->id}"),
            new PrivateChannel("creator-bookings.{$this->bookingRequest->creator_identity_id}"),
            new PrivateChannel("client-bookings.{$this->bookingRequest->client_identity_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'data' => new BookingRequestResource($this->bookingRequest),
            'previous_status' => $this->previousStatus,
        ];
    }
}
