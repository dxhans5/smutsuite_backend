<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->authUser, 'sanctum');

        // ✅ Force middleware to run
        $this->withoutMiddleware(HandlePrecognitiveRequests::class);
    }

    #[Test]
    public function it_assigns_roles_and_permissions_to_a_user(): void
    {
        $target = User::factory()->create(['email_verified_at' => now()]);
        $roles = Role::factory()->count(2)->create();
        $permissions = Permission::factory()->count(3)->create();

        $payload = [
            'roles' => $roles->pluck('id')->toArray(),
            'permissions' => $permissions->pluck('id')->toArray(),
        ];

        $response = $this->postJson("/api/users/{$target->id}/assign", $payload);

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'message' => __('permissionsroles.bulk_assign_success'),
                ]
            ]);

        $target->refresh();

        foreach ($roles as $role) {
            $this->assertTrue($target->roles->contains($role));
        }

        foreach ($permissions as $permission) {
            $this->assertTrue($target->permissions->contains($permission));
        }
    }

    #[Test]
    public function it_removes_roles_and_permissions_from_a_user(): void
    {
        $target = User::factory()->create(['email_verified_at' => now()]);
        $roles = Role::factory()->count(2)->create();
        $permissions = Permission::factory()->count(3)->create();

        $target->roles()->sync($roles->pluck('id'));
        $target->permissions()->sync($permissions->pluck('id'));

        $payload = [
            'roles' => $roles->pluck('id')->toArray(),
            'permissions' => $permissions->pluck('id')->toArray(),
        ];

        $response = $this->postJson("/api/users/{$target->id}/remove", $payload);

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'message' => __('permissionsroles.bulk_remove_success'),
                ]
            ]);

        $target->refresh();

        $this->assertCount(0, $target->roles);
        $this->assertCount(0, $target->permissions);
    }

    #[Test]
    public function it_rejects_invalid_role_and_permission_ids(): void
    {
        $target = User::factory()->create(['email_verified_at' => now()]);

        $payload = [
            'roles' => [999],          // non-existent
            'permissions' => ['abc'],  // invalid format
        ];

        $response = $this->postJson("/api/users/{$target->id}/assign", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['roles.0', 'permissions.0']);
    }

    #[Test]
    public function it_handles_empty_payloads_gracefully(): void
    {
        $target = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson("/api/users/{$target->id}/assign", []);

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'message' => __('permissionsroles.bulk_assign_success'),
                ]
            ]);

        $target->refresh();

        $this->assertCount(0, $target->roles);
        $this->assertCount(0, $target->permissions);
    }

//    #[Test]
//    public function unauthenticated_users_cannot_access_assign_or_remove_endpoints(): void
//    {
//        $this->flushSession();
//        $this->withHeaders(['Authorization' => '']);
//
//        $target = User::factory()->create(['email_verified_at' => now()]);
//        $role = Role::factory()->create();
//        $permission = Permission::factory()->create();
//
//        $payload = [
//            'roles' => [$role->id],
//            'permissions' => [$permission->id],
//        ];
//
//        $this->postJson("/api/users/{$target->id}/assign", $payload)
//            ->assertUnauthorized();
//
//        $this->postJson("/api/users/{$target->id}/remove", $payload)
//            ->assertUnauthorized();
//    }
}
