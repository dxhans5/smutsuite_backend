<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use App\Models\MessageThread;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array {
        return [
            'message_thread_id' => MessageThread::factory(),
            'sender_id' => User::factory(),
            'body' => $this->faker->paragraph,
        ];
    }
}
