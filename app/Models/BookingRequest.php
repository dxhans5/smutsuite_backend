<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Events\BookingRequestStatusChanged;

class BookingRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'creator_identity_id',
        'client_identity_id',
        'requested_at',
        'booking_type',
        'status',
        'notes',
        'timezone',
    ];

    protected $casts = [
        'booking_type' => BookingType::class,
        'status' => BookingStatus::class,
        'requested_at' => 'datetime',
    ];

    // Add relationships and status transition methods
    public function canTransitionTo(BookingStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function updateStatus(BookingStatus $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $previousStatus = $this->status->value;
        $this->status = $newStatus;
        $this->save();

        // Trigger real-time broadcast
        broadcast(new BookingRequestStatusChanged($this, $previousStatus));

        return true;
    }
}
