<?php

namespace Tests\Feature\Seeders;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Validates the behavior and integrity of the RoleSeeder.
 */
class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * It seeds all expected roles and no extras.
     */
    #[Test]
    public function it_seeds_all_expected_roles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $expected = [
            'admin',
            'user',
            'creator',   // formerly content_provider
            'provider',  // formerly service_provider
            'host',
        ];

        foreach ($expected as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $this->assertEquals(
            count($expected),
            Role::count(),
            'Unexpected number of roles in the database.'
        );
    }

    /**
     * Role names must be unique in the database.
     */
    #[Test]
    public function role_names_are_unique(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->expectException(QueryException::class);

        Role::create([
            'name' => 'admin',
            'description' => 'Duplicate role test',
        ]);
    }
}
