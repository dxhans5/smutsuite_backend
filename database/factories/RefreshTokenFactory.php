<?php

namespace Database\Factories;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'user_agent' => $this->faker->userAgent,
            'ip_address' => $this->faker->ipv4,
            'expires_at' => Carbon::now()->addDays(30),
        ];
    }
}
