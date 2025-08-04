<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\PublicProfile;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function unauthenticated_users_cannot_access_profile_endpoints(): void {
        $this->getJson('/api/profiles/me')->assertUnauthorized();
        $this->putJson('/api/profiles/me/public', [])->assertUnauthorized();
        $this->putJson('/api/profiles/me/private', [])->assertUnauthorized();
    }

    #[Test]
    public function user_can_update_and_retrieve_own_public_profile(): void {
        $payload = [
            'display_name' => 'KittenDomme',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'tagline' => 'Your favorite troublemaker',
            'about' => 'Loving, mean, and highly rated.',
            'is_visible' => true,
            'hide_from_locals' => false,
            'role' => 'creator',
            'location' => 'Las Vegas',
        ];

        $this->actingAs($this->user)
            ->putJson('/api/profiles/me/public', $payload)
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'KittenDomme']);

        $this->assertDatabaseHas('public_profiles', [
            'user_id' => $this->user->id,
            'display_name' => 'KittenDomme',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/profiles/me')
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'KittenDomme']);
    }

    #[Test]
    public function user_can_update_and_retrieve_own_private_profile(): void {
        $payload = [
            'notes' => ['client_a' => 'Good sub'],
            'journal' => ['2025-08-04' => 'Felt empowered after session'],
            'favorite_kinks' => ['bondage', 'worship'],
            'mood' => 'Calm',
            'emotional_state' => 'Recharged',
            'timezone' => 'America/Chicago',
        ];

        $this->actingAs($this->user)
            ->putJson('/api/profiles/me/private', $payload)
            ->assertOk()
            ->assertJsonFragment(['mood' => 'Calm']);

        $this->assertDatabaseHas('private_profiles', [
            'user_id' => $this->user->id,
            'mood' => 'Calm',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/profiles/me')
            ->assertOk()
            ->assertJsonFragment(['emotional_state' => 'Recharged']);
    }

    #[Test]
    public function public_profile_can_only_be_seen_if_visible(): void {
        $visibleProfile = PublicProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'VisibleUser',
            'is_visible' => true,
        ]);

        $hiddenProfile = PublicProfile::factory()->create([
            'display_name' => 'HiddenUser',
            'is_visible' => false,
        ]);

        // Visible one should show
        $this->actingAs($this->user)
            ->getjson('/api/profiles/' . $visibleProfile->user_id . '/public')
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'VisibleUser']);

        // Hidden one should 404
        $this->actingAs($this->user)
            ->getJson('/api/profiles/' . $hiddenProfile->user_id . '/public')
            ->assertNotFound();
    }
}
