<?php

namespace App\Events;

use App\Models\Identity;
use App\Models\AvailabilityRule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AvailabilityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Identity $creatorIdentity,
        public ?AvailabilityRule $availabilityRule = null,
        public string $updateType = 'schedule_changed'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("identity.{$this->creatorIdentity->id}"),
            new Channel('discovery'),
            new PrivateChannel("availability.{$this->creatorIdentity->id}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'creator_identity_id' => $this->creatorIdentity->id,
            'creator_name' => $this->creatorIdentity->alias,  // Changed from display_name to alias
            'update_type' => $this->updateType,
            'availability_rule' => $this->availabilityRule?->toArray(),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'availability.updated';
    }
}
