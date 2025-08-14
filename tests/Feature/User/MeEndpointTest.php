<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ Authenticated user receives profile data under the unified `data` envelope,
     * including roles and merged permissions (direct + via roles).
     */
    #[Test]
    public function it_returns_authenticated_user_profile_data(): void
    {
        // Create and verify a user
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        // Role + permissions setup
        $role  = Role::firstOrCreate(['name' => 'creator'], ['description' => 'Test role for creators']);
        $perm1 = Permission::factory()->create(['name' => 'bookings.view']);
        $perm2 = Permission::factory()->create(['name' => 'messages.send']);
        $perm3 = Permission::factory()->create(['name' => 'admin.panel']);

        // Attach permissions to role and assign role + direct permission to the user
        $role->permissions()->attach([$perm1->id, $perm2->id]);
        $user->roles()->attach($role->id);
        $user->permissions()->attach($perm3->id);

        // Prime relations for clarity
        $user->load('roles.permissions', 'permissions');

        // Act as the user and call /api/auth/me
        $response = $this->actingAs($user)->getJson('/api/auth/me');

        // 1) Basic contract... top-level `data` only
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'email',
                'roles',
                'email_verified',
                'created_at',
                'updated_at',
                'permissions',
            ],
        ]);

        // 2) Core profile info
        $response->assertJsonFragment(['email' => $user->email]);

        // 3) Permissions... two via role + one direct
        $permissions = $response->json('data.permissions');
        $this->assertIsArray($permissions, 'data.permissions should be an array');
        $this->assertContains('bookings.view', $permissions);
        $this->assertContains('messages.send', $permissions);
        $this->assertContains('admin.panel', $permissions);
        $this->assertCount(3, $permissions);
    }

    /**
     * 🚫 Guests cannot access /me.
     */
    #[Test]
    public function it_denies_access_to_guests(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }
}
