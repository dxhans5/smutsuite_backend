<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RefreshToken;
use App\Models\User;

class RefreshTokenSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(5)
            ->create()
            ->each(function ($user) {
                RefreshToken::factory()->count(2)->create([
                    'user_id' => $user->id,
                ]);
            });
    }
}
