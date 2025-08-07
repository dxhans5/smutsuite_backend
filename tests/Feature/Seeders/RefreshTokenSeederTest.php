<?php

namespace Tests\Feature\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests related to the RefreshToken seeder behavior.
 *
 * Note: By design, the RefreshToken table should start empty.
 * This test ensures no tokens are preloaded unless explicitly seeded.
 */
class RefreshTokenSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_does_not_seed_refresh_tokens_by_default(): void
    {
        $this->assertDatabaseCount('refresh_tokens', 0);
    }

    /**
     * Optional test for future seeder implementation.
     *
     * Uncomment if/when a RefreshTokenSeeder is added to the project.
     */
    // #[Test]
    // public function it_seeds_expected_tokens(): void
    // {
    //     $this->seed(\Database\Seeders\RefreshTokenSeeder::class);
    //     $this->assertGreaterThan(0, \App\Models\RefreshToken::count(), 'Seeder should create refresh tokens.');
    // }
}
