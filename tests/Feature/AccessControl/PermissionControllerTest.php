<?php
declare(strict_types=1);

namespace Tests\Feature\AccessControl;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        // If your routes are guarded by Sanctum, this is enough for auth.
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    }

    #[Test]
    public function it_attaches_a_permission_and_returns_envelope(): void
    {
        $target = User::factory()->create();
        $perm   = Permission::factory()->create();

        $res = $this->postJson("/api/users/{$target->id}/permissions/{$perm->id}");

        $res->assertOk()
            ->assertJsonStructure([
                'data' => ['user_id', 'permission_id', 'attached'],
                'meta' => ['success', 'message', 'timestamp'],
            ])
            ->assertJsonPath('meta.success', true)
            ->assertJsonPath('data.attached', true);

        $this->assertTrue(
            $target->permissions()->whereKey($perm->id)->exists(),
            'Permission should be attached to the user.'
        );
    }

    #[Test]
    public function it_conflicts_when_attaching_a_permission_twice(): void
    {
        $target = User::factory()->create();
        $perm   = Permission::factory()->create();

        $target->permissions()->attach($perm->id);

        $res = $this->postJson("/api/users/{$target->id}/permissions/{$perm->id}");

        $res->assertStatus(409)
            ->assertJsonStructure([
                'data',
                'meta' => ['success', 'message', 'timestamp', 'user_id', 'permission_id'],
            ])
            ->assertJsonPath('meta.success', false);
    }

    #[Test]
    public function it_detaches_a_permission_and_returns_envelope(): void
    {
        $target = User::factory()->create();
        $perm   = Permission::factory()->create();

        $target->permissions()->attach($perm->id);

        $res = $this->deleteJson("/api/users/{$target->id}/permissions/{$perm->id}");

        $res->assertOk()
            ->assertJsonStructure([
                'data' => ['user_id', 'permission_id', 'detached'],
                'meta' => ['success', 'message', 'timestamp'],
            ])
            ->assertJsonPath('meta.success', true)
            ->assertJsonPath('data.detached', true);

        $this->assertFalse(
            $target->permissions()->whereKey($perm->id)->exists(),
            'Permission should be detached from the user.'
        );
    }

    #[Test]
    public function it_404s_when_detaching_a_permission_that_is_not_attached(): void
    {
        $target = User::factory()->create();
        $perm   = Permission::factory()->create();

        $res = $this->deleteJson("/api/users/{$target->id}/permissions/{$perm->id}");

        $res->assertStatus(404)
            ->assertJsonStructure([
                'data',
                'meta' => ['success', 'message', 'timestamp', 'user_id', 'permission_id'],
            ])
            ->assertJsonPath('meta.success', false);
    }

    #[Test]
    public function it_bulk_assigns_roles_and_permissions_without_detaching_existing(): void
    {
        $target = User::factory()->create();

        // Create a couple roles and permissions
        $roles = Role::factory()->count(2)->create();
        $perms = Permission::factory()->count(2)->create();

        // Pre-attach one item of each type to ensure "without detaching" behavior
        $target->roles()->attach($roles[0]->id);
        $target->permissions()->attach($perms[0]->id);

        $payload = [
            'roles'       => $roles->pluck('id')->all(),
            'permissions' => $perms->pluck('id')->all(),
        ];

        $res = $this->postJson("/api/users/{$target->id}/permissions/bulk", $payload);

        $res->assertOk()
            ->assertJsonStructure([
                'data' => ['user_id', 'requested_role_ids', 'requested_permission_ids'],
                'meta' => ['success', 'message', 'timestamp', 'roles_total', 'permissions_total'],
            ])
            ->assertJsonPath('meta.success', true);

        // Still has all roles/perms (nothing detached)
        foreach ($roles as $r) {
            $this->assertTrue($target->fresh()->roles()->whereKey($r->id)->exists());
        }
        foreach ($perms as $p) {
            $this->assertTrue($target->fresh()->permissions()->whereKey($p->id)->exists());
        }
    }

    #[Test]
    public function it_bulk_removes_roles_and_permissions(): void
    {
        $target = User::factory()->create();

        $roles = Role::factory()->count(2)->create();
        $perms = Permission::factory()->count(2)->create();

        $target->roles()->attach($roles->pluck('id')->all());
        $target->permissions()->attach($perms->pluck('id')->all());

        $payload = [
            'roles'       => $roles->pluck('id')->all(),
            'permissions' => $perms->pluck('id')->all(),
        ];

        $res = $this->deleteJson("/api/users/{$target->id}/permissions/bulk", $payload);

        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'removed_role_ids',
                    'removed_permission_ids',
                    'roles_detached_count',
                    'permissions_detached_count',
                ],
                'meta' => ['success', 'message', 'timestamp'],
            ])
            ->assertJsonPath('meta.success', true);

        foreach ($roles as $r) {
            $this->assertFalse($target->fresh()->roles()->whereKey($r->id)->exists());
        }
        foreach ($perms as $p) {
            $this->assertFalse($target->fresh()->permissions()->whereKey($p->id)->exists());
        }
    }

    #[Test]
    public function bulk_endpoints_allow_empty_payloads_as_noops(): void
    {
        $target = User::factory()->create();

        $this->postJson("/api/users/{$target->id}/permissions/bulk", [])
            ->assertOk()
            ->assertJsonPath('meta.success', true);

        $this->deleteJson("/api/users/{$target->id}/permissions/bulk", [])
            ->assertOk()
            ->assertJsonPath('meta.success', true);
    }
}
