<?php

namespace App\Events;

use App\Models\Message;
use App\Http\Resources\MessageResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public string $messageThreadId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("message-thread.{$this->messageThreadId}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'data' => new MessageResource($this->message)
        ];
    }
}
