<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\BookingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRequestFactory extends Factory
{
    protected $model = BookingRequest::class;

    public function definition(): array {
        return [
            'creator_id' => User::factory(),
            'client_id' => User::factory(),
            'requested_at' => now()->addDays(2)->setTime(14, 0),
            'booking_type' => 'chat',
            'status' => 'pending',
            'notes' => $this->faker->sentence,
            'timezone' => 'America/Chicago',
        ];
    }
}
