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
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/attach")
            ->assertOk()
            ->assertJson(['message' => __('permissionsroles.permission_attach_success')]);

        $this->assertTrue($user->fresh()->permissions->contains($permission));
    }

    #[Test]
    public function user_can_detach_permission_from_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $user->permissions()->attach($permission);

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/detach")
            ->assertOk()
            ->assertJson(['message' => __('permissionsroles.permission_detach_success')]);

        $this->assertFalse($user->fresh()->permissions->contains($permission));
    }

    #[Test]
    public function attach_fails_if_user_not_found(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::factory()->create();

        $nonexistentUuid = Str::uuid()->toString(); // valid format, guaranteed to not exist

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
        $user = User::factory()->create();

        $permission = Permission::factory()->create();

        $user->permissions()->attach($permission);

        $this->actingAs($admin)
            ->postJson("/api/users/{$user->id}/permissions/{$permission->id}/attach")
            ->assertStatus(409)
            ->assertJson(['message' => __('permissionsroles.permission_already_attached')]); // No error, but should not duplicate
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
