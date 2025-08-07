<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Identity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login returns auth token and correct user/identity data.
     */
    #[Test]
    public function login_succeeds_and_returns_user_with_active_identity(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123!'),
        ]);

        $identity = Identity::factory()
            ->for($user)
            ->create([
                'id'     => Str::uuid()->toString(),
                'role'   => 'creator',
                'is_active' => true,
            ]);

        $user->update(['active_identity_id' => $identity->id]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'email',
                        'roles',
                        'permissions',
                        'email_verified',
                        'active_identity',
                        'identities',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJsonPath('data.user.active_identity.id', $identity->id);
    }

    /**
     * Test login fails with invalid credentials.
     */
    #[Test]
    public function login_fails_with_bad_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123!'),
        ]);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'invalid-password',
        ])
            ->assertUnauthorized()
            ->assertJson(['message' => __('auth.failed')]);
    }

    /**
     * Test authenticated /me endpoint returns correct user and identities.
     */
    #[Test]
    public function me_returns_user_with_identities_and_permissions(): void
    {
        $user = User::factory()->create();

        $creator = Identity::factory()
            ->for($user)
            ->create(['role' => 'creator', 'is_active' => true]);

        $user->active_identity_id = $creator->id;
        $user->save();

        $client = Identity::factory()
            ->for($user)
            ->create(['role' => 'user', 'is_active' => true]);

        $user->update(['active_identity_id' => $creator->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['identities']])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.active_identity.id', $creator->id)
            ->assertJsonCount(2, 'data.identities');
    }

//    /**
//     * Test identity switching enforces ownership and 'active' status.
//     */
//    #[Test]
//    public function switch_identity_requires_ownership_and_active_status(): void
//    {
//        $user = User::factory()->create();
//
//        $mine = Identity::factory()
//            ->for($user)
//            ->create([
//                'role' => 'creator',
//                'verification_status' => 'verified',
//                'is_active' => false, // will be activated by switch()
//            ]);
//
//        $otherUser = User::factory()->create();
//
//        $theirs = Identity::factory()
//            ->for($otherUser)
//            ->create(['role' => 'creator', 'is_active' => true]);
//
//        $suspended = Identity::factory()
//            ->for($user)
//            ->create([
//                'role' => 'creator',
//                'verification_status' => 'verified',
//                'is_active' => false, // will be activated by switch());
//            ]);
//
//        Sanctum::actingAs($user);
//
//        // ✅ Should allow switching to own active identity
//        $this->postJson('/api/identities/switch', ['identity_id' => $mine->id])
//            ->assertOk()
//            ->assertJsonPath('data.active_identity_id', $mine->id);
//
//        // ❌ Should reject switching to another user's identity
//        $this->postJson('/api/identities/switch', ['identity_id' => $theirs->id])
//            ->assertForbidden();
//
//        // ❌ Should reject switching to suspended identity
//        $this->postJson('/api/identities/switch', ['identity_id' => $suspended->id])
//            ->assertForbidden();
//    }

    /**
     * Test logout revokes the user's token.
     */
    #[Test]
    public function logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $tokenObject = $user->createToken('auth_token');
        $plainToken = $tokenObject->plainTextToken;

        $this->withHeaders([
            'Authorization' => "Bearer {$plainToken}"
        ])->postJson('/api/auth/logout')->assertOk();

        $this->withHeaders([
            'Authorization' => "Bearer {$plainToken}"
        ])->postJson('/api/auth/logout')->assertUnauthorized();
    }

    /**
     * Test logout endpoint fails for unauthenticated users.
     */
    #[Test]
    public function logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized()
            ->assertJson(['message' => __('auth.unauthenticated')]);
    }
}
