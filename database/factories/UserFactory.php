<?php

namespace Database\Factories;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'display_name' => $this->faker->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'date_of_birth' => now()->subYears($this->faker->numberBetween(21, 50)),
            'role' => 'user',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function configure(): Factory|UserFactory
    {
        return $this->afterCreating(function (User $user) {
            if (!$user->active_identity_id) {
                $identity = Identity::factory()->create([
                    'user_id' => $user->id,
                    'is_active' => true,
                ]);
                $user->update(['active_identity_id' => $identity->id]);
            }
        });
    }
}
