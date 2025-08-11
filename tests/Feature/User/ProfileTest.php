<?php

namespace Tests\Feature\User;

use App\Models\Identity;
use Tests\TestCase;
use App\Models\User;
use App\Models\PublicProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for user profile management (public & private).
 *
 * Covers:
 * - Auth access restrictions
 * - Public/private profile updates
 * - Profile visibility rules
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    /**
     * Create a verified user for testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Unauthenticated users should be denied access to profile endpoints.
     */
    #[Test]
    public function unauthenticated_users_cannot_access_profile_endpoints(): void
    {
        $this->getJson('/api/profiles/me')->assertUnauthorized();
        $this->putJson('/api/profiles/me/public', [])->assertUnauthorized();
        $this->putJson('/api/profiles/me/private', [])->assertUnauthorized();
    }

    /**
     * User can update and retrieve their own public profile.
     */
    #[Test]
    public function user_can_update_and_retrieve_own_public_profile(): void
    {
        $identity = Identity::factory()->for($this->user)->create(['is_active' => true]);
        $this->user->update(['active_identity_id' => $identity->id]);

        $payload = [
            'display_name'     => 'KittenDomme',
            'avatar_url'       => 'https://example.com/avatar.jpg',
            'tagline'          => 'Your favorite troublemaker',
            'about'            => 'Loving, mean, and highly rated.',
            'is_visible'       => true,
            'hide_from_locals' => false,
            'role'             => 'creator',
            'location'         => 'Las Vegas',
        ];

        $this->actingAs($this->user)
            ->putJson('/api/profiles/me/public', $payload)
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'KittenDomme']);

        $this->assertDatabaseHas('public_profiles', [
            'identity_id'  => $identity->id,
            'display_name' => 'KittenDomme',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/profiles/me')
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'KittenDomme']);
    }

    /**
     * User can update and retrieve their own private profile.
     */
    #[Test]
    public function user_can_update_and_retrieve_own_private_profile(): void
    {
        $identity = Identity::factory()->for($this->user)->create([
            'is_active' => true,
        ]);

        $this->user->update(['active_identity_id' => $identity->id]);

        $payload = [
            'notes'           => ['client_a' => 'Good sub'],
            'journal'         => ['2025-08-04' => 'Felt empowered after session'],
            'favorite_kinks'  => ['bondage', 'worship'],
            'mood'            => 'Calm',
            'emotional_state' => 'Recharged',
            'timezone'        => 'America/Chicago',
        ];

        $this->actingAs($this->user)
            ->putJson('/api/profiles/me/private', $payload)
            ->assertOk()
            ->assertJsonFragment(['mood' => 'Calm']);

        $this->assertDatabaseHas('private_profiles', [
            'identity_id' => $identity->id,
            'mood'        => 'Calm',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/profiles/me')
            ->assertOk()
            ->assertJsonFragment(['emotional_state' => 'Recharged']);
    }


    /**
     * Public profiles should only be visible if `is_visible` is true.
     */
    #[Test]
    public function public_profile_can_only_be_seen_if_visible(): void
    {
        $visibleIdentity = Identity::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->user->update(['active_identity_id' => $visibleIdentity->id]);

        $visibleProfile = PublicProfile::factory()->create([
            'identity_id'  => $visibleIdentity->id,
            'display_name' => 'VisibleUser',
            'is_visible'   => true,
        ]);

        $hiddenIdentity = Identity::factory()->create();

        $hiddenProfile = PublicProfile::factory()->create([
            'identity_id'  => $hiddenIdentity->id,
            'display_name' => 'HiddenUser',
            'is_visible'   => false,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/profiles/{$visibleIdentity->id}/public")
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'VisibleUser']);

        $this->actingAs($this->user)
            ->getJson("/api/profiles/{$hiddenIdentity->id}/public")
            ->assertNotFound();
    }
}
