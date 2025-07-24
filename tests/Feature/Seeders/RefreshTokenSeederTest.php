<?php

namespace Tests\Feature\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenSeederTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_seed_refresh_tokens_by_default(): void
    {
        $this->assertDatabaseCount('refresh_tokens', 0);
    }

    // Optional: If you create a seeder later
    // #[\PHPUnit\Framework\Attributes\Test]
    // public function it_seeds_expected_tokens(): void
    // {
    //     $this->seed(\Database\Seeders\RefreshTokenSeeder::class);
    //     $this->assertGreaterThan(0, \App\Models\RefreshToken::count());
    // }
}
