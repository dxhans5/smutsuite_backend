<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'message_thread_id'    => MessageThread::factory(),
            'sender_identity_id'   => Identity::factory(),
            'body'                 => $this->faker->sentence,
        ];
    }
}
