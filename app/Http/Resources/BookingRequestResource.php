<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator_identity_id' => $this->creator_identity_id,
            'client_identity_id' => $this->client_identity_id,
            'requested_at' => $this->requested_at->toISOString(),
            'booking_type' => $this->booking_type->value,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
