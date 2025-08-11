<?php

namespace Database\Factories;

use App\Models\PublicProfile;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating public profile records.
 *
 * Each profile is associated with an Identity, not a User directly.
 */
class PublicProfileFactory extends Factory
{
    protected $model = PublicProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identity_id'       => Identity::factory(),
            'display_name'      => $this->faker->userName,
            'avatar_url'        => $this->faker->imageUrl,
            'tagline'           => $this->faker->sentence,
            'about'             => $this->faker->paragraph,
            'is_visible'        => true,
            'hide_from_locals'  => false,
            'role'              => 'creator',
            'location'          => $this->faker->city,
        ];
    }
}
