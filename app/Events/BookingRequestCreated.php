<?php

namespace App\Events;

use App\Models\BookingRequest;
use App\Http\Resources\BookingRequestResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRequestCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public BookingRequest $bookingRequest) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("creator-bookings.{$this->bookingRequest->creator_identity_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'data' => new BookingRequestResource($this->bookingRequest),
        ];
    }
}
