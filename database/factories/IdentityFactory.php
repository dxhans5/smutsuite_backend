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
            'type' => $this->faker->randomElement(['user', 'creator', 'host', 'service_provider']),
            'visibility' => 'public',
            'verification_status' => 'pending',
            'is_active' => false,
        ];
    }
}
