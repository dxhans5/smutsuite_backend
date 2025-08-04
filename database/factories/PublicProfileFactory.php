<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PublicProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublicProfileFactory extends Factory
{
    protected $model = PublicProfile::class;

    public function definition(): array {
        return [
            'user_id' => User::factory(),
            'display_name' => $this->faker->userName,
            'avatar_url' => $this->faker->imageUrl,
            'tagline' => $this->faker->sentence,
            'about' => $this->faker->paragraph,
            'is_visible' => true,
            'hide_from_locals' => false,
            'role' => 'creator',
            'location' => $this->faker->city,
        ];
    }
}
