<?php

namespace Database\Factories;

use App\Models\Identity;
use App\Models\BookingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRequestFactory extends Factory
{
    protected $model = BookingRequest::class;

    public function definition(): array {
        return [
            'creator_identity_id' => Identity::factory(),
            'client_identity_id' => Identity::factory(),
            'requested_at' => now()->addDays(2)->setTime(14, 0),
            'booking_type' => 'chat',
            'status' => 'pending',
            'notes' => $this->faker->sentence,
            'timezone' => 'America/Chicago',
        ];
    }
}
