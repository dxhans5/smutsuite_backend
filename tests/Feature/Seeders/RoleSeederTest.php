<?php

namespace Tests\Feature\Seeders;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_all_expected_roles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $expected = [
            'admin',
            'user',
            'content_provider',
            'service_provider',
            'host',
        ];

        foreach ($expected as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $this->assertEquals(
            count($expected),
            Role::count(),
            'Unexpected number of roles in the database'
        );
    }

    #[Test]
    public function role_names_are_unique(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Role::create([
            'name' => 'admin',
            'description' => 'Duplicate test',
        ]);
    }
}
