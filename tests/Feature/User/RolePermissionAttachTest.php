<?php

namespace Tests\Feature\User;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionAttachTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions if they don't exist (check first to avoid duplicates)
        Permission::firstOrCreate(['name' => 'view_personal_dashboard'], ['description' => 'Test permission']);
        Permission::firstOrCreate(['name' => 'edit_users'], ['description' => 'Test permission']);
        Permission::firstOrCreate(['name' => 'manage_bookings'], ['description' => 'Test permission']);

        // Get or create admin role (don't duplicate)
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator role']);
    }

    #[Test]
    public function user_can_attach_role_to_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/roles/{$role->id}/attach")
            ->assertOk()
            ->assertJson(['message' => __('permissionsroles.role_attach_success')]);

        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    #[Test]
    public function user_can_detach_role_from_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $user->roles()->attach($role);

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/roles/{$role->id}/detach")
            ->assertOk()
            ->assertJson(['message' => __('permissionsroles.role_detach_success')]);

        $this->assertFalse($user->fresh()->roles->contains($role));
    }

    #[Test]
    public function user_can_attach_permission_to_user(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create();
        $permission = Permission::where('name', 'view_personal_dashboard')->first();

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/attach")
            ->assertOk()
            ->assertJsonPath('meta.message', __('permissionsroles.permission_attach_success'));

        $this->assertTrue($user->fresh()->permissions->contains($permission));
    }

    #[Test]
    public function user_can_detach_permission_from_user(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create();
        $permission = Permission::where('name', 'view_personal_dashboard')->first();
        $user->permissions()->attach($permission);

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/detach")
            ->assertOk()
            ->assertJsonPath('meta.message', __('permissionsroles.permission_detach_success'));

        $this->assertFalse($user->fresh()->permissions->contains($permission));
    }

    #[Test]
    public function attach_fails_if_user_not_found(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::factory()->create();

        $nonexistentUuid = Str::uuid()->toString();

        $this->actingAs($admin)
            ->postJson("/api/users/{$nonexistentUuid}/roles/{$role->id}/attach")
            ->assertNotFound();
    }

    #[Test]
    public function attach_fails_if_role_not_found(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/roles/999999/attach")
            ->assertNotFound();
    }

    #[Test]
    public function permission_attach_fails_if_already_attached(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);

        $user = User::factory()->create();
        $permission = Permission::where('name', 'view_personal_dashboard')->first();
        $user->permissions()->attach($permission);

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/attach")
            ->assertStatus(409)
            ->assertJsonPath('meta.message', __('permissionsroles.permission_already_attached'));
    }

    #[Test]
    public function unauthorized_user_cannot_attach_or_detach(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $role = Role::factory()->create();

        $this->postJson("/api/users/{$target->id}/roles/{$role->id}/attach")
            ->assertUnauthorized();

        $this->postJson("/api/users/{$target->id}/roles/{$role->id}/detach")
            ->assertUnauthorized();
    }
}
