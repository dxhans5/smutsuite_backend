<?php

namespace Tests\Feature\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a role can be created with a name and description.
     */
    #[Test]
    public function it_can_create_a_role(): void
    {
        $role = Role::create([
            'name'        => 'moderator',
            'description' => 'Can moderate content and users.',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'moderator',
        ]);
    }

    #[Test]
    public function role_name_must_be_unique(): void
    {
        Role::create(['name' => 'test_unique']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Role::create(['name' => 'test_unique']);
    }

    /**
     * Test the users() relationship on the Role model.
     * Ensure it's a BelongsToMany and functions correctly.
     */
    #[Test]
    public function it_has_users_relationship(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $role->users()->attach($user->id);

        $this->assertTrue(method_exists($role, 'users'));
        $this->assertInstanceOf(BelongsToMany::class, $role->users());
        $this->assertTrue($role->users->contains($user));
    }

    /**
     * Sanity check that the roles table exists and has expected columns.
     */
    #[Test]
    public function roles_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'description', 'created_at', 'updated_at',
        ]));
    }
}
