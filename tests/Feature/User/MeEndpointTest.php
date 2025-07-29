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

    #[Test]
    public function authenticated_user_can_retrieve_their_profile_data()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $role = Role::factory()->create(['name' => 'creator']);
        $perm1 = Permission::factory()->create(['name' => 'bookings.view']);
        $perm2 = Permission::factory()->create(['name' => 'messages.send']);
        $perm3 = Permission::factory()->create(['name' => 'admin.panel']);

        // Give role two permissions
        $role->permissions()->attach([$perm1->id, $perm2->id]);

        // Give user the role
        $user->roles()->attach($role->id);

        // Give user one direct permission
        $user->permissions()->attach($perm3->id);

        $user->load('roles.permissions', 'permissions');
        $response = $this->actingAs($user)->getJson('/api/me');

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

        $response->assertJsonFragment([
            'email' => $user->email,
        ]);

        // Permissions returned should include all 3 (2 from role, 1 direct)
        $responseData = $response->json('data');
        $this->assertContains('bookings.view', $responseData['permissions']);
        $this->assertContains('messages.send', $responseData['permissions']);
        $this->assertContains('admin.panel', $responseData['permissions']);
        $this->assertCount(3, $responseData['permissions']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_me_endpoint()
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }
}
