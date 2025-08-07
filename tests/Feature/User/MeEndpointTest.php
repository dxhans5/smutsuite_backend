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
     * ✅ Authenticated user should receive their full profile data,
     * including assigned roles and permissions (both direct and via roles).
     */
    #[Test]
    public function it_returns_authenticated_user_profile_data(): void
    {
        // Create and verify a user
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        // Create a role and permissions
        $role = Role::firstOrCreate(
            ['name' => 'creator'],
            ['description' => 'Test role for creators']
        );
        $perm1 = Permission::factory()->create(['name' => 'bookings.view']);
        $perm2 = Permission::factory()->create(['name' => 'messages.send']);
        $perm3 = Permission::factory()->create(['name' => 'admin.panel']);

        // Attach permissions to role
        $role->permissions()->attach([$perm1->id, $perm2->id]);

        // Assign role and a direct permission to the user
        $user->roles()->attach($role->id);
        $user->permissions()->attach($perm3->id);

        // Load relationships before API call
        $user->load('roles.permissions', 'permissions');

        // Hit /api/me as the authenticated user
        $response = $this->actingAs($user)->getJson('/api/auth/me');

        // Assert basic response structure
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
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

        // Assert core profile info
        $response->assertJsonFragment(['email' => $user->email]);

        // Assert all expected permissions (2 via role, 1 direct)
        $permissions = $response->json('data.permissions');
        $this->assertContains('bookings.view', $permissions);
        $this->assertContains('messages.send', $permissions);
        $this->assertContains('admin.panel', $permissions);
        $this->assertCount(3, $permissions);
    }

    /**
     * 🚫 Guests should not be able to access the /me endpoint.
     */
    #[Test]
    public function it_denies_access_to_guests(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }
}
