<?php

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests the PermissionSeeder to ensure it correctly seeds
 * expected permission records into the database.
 */
class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Ensure the PermissionSeeder inserts expected permission data.
     */
    #[Test]
    public function it_seeds_expected_permissions(): void
    {
        // Act: Run the permission seeder
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        // Assert: Check that a known permission exists
        $this->assertDatabaseHas('permissions', [
            'name' => 'view_personal_dashboard',
            'description' => 'Allows access to the personal dashboard',
        ]);
    }
}
