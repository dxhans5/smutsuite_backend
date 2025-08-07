<?php

namespace Database\Factories;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IdentityFactory extends Factory
{
    protected $model = Identity::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'alias' => $this->faker->unique()->userName(),
            'role' => $this->faker->randomElement(['user', 'creator', 'host', 'service_provider']),
            'visibility_level' => 'public',
            'verification_status' => 'pending',
            'payout_method_id' => null,
            'is_active' => false,
        ];
    }
}
