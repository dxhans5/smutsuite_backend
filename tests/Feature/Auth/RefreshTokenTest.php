<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_issues_a_refresh_token_on_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'refresh_token']]);

        $this->assertDatabaseCount('refresh_tokens', 1);
    }

    #[Test]
    public function it_returns_new_token_pair_when_refreshing_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()->make([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $refreshToken->hash($token); // saves

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'refresh_token']]);
    }

    #[Test]
    public function it_rejects_invalid_refresh_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'fake-token',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => __('auth.invalid_refresh_token')]);
    }

    #[Test]
    public function it_rejects_expired_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()->make([
            'user_id' => $user->id,
            'expires_at' => now()->subMinute(),
        ]);

        $refreshToken->hash($token); // saves

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => __('auth.expired_refresh_token')]);
    }

    #[Test]
    public function it_revokes_old_refresh_token_after_rotation(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
            'token_hash' => Hash::make($token),
        ]);
        $refreshTokenId = $refreshToken->id; // Save ID now

        // Trigger the rotation
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $token,
        ]);

        // Re-fetch by known ID (controller should have revoked this)
        $revoked = RefreshToken::findOrFail($refreshTokenId);

        $this->assertNotNull($revoked->revoked_at, __('auth.refresh_not_revoked'));
        dump(RefreshToken::find($refreshToken->id)->toArray());

    }
}
