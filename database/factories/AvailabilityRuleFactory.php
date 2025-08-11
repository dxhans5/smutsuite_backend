<?php

namespace Database\Factories;

use App\Models\AvailabilityRule;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityRuleFactory extends Factory
{
    protected $model = AvailabilityRule::class;

    public function definition(): array {
        return [
            'identity_id' => Identity::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => '10:00',
            'end_time' => '18:00',
            'booking_type' => 'chat',
            'is_active' => true,
        ];
    }
}
