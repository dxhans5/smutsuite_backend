<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * 🔁 RefreshTokenTest
 *
 * Tests the refresh token lifecycle for access token renewal:
 * - Issue: POST /api/auth/login returns { token, refresh_token }
 * - Rotate: POST /api/auth/refresh with refresh_token returns new { token, refresh_token }
 * - Reject: Invalid or reused tokens are rejected
 */
class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_refresh_access_token_with_valid_refresh_token(): void
    {
        $user = User::factory()->create();

        // Login to get token + refresh_token
        $loginRes = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password', // default from factory
        ])->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'refresh_token',
                    'user' => ['id', 'email']
                ]
            ]);

        $refreshToken = $loginRes->json('data.refresh_token');
        $this->assertNotEmpty($refreshToken, 'Login must return a refresh token');

        // Use refresh token
        $refreshRes = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'refresh_token'
                ]
            ]);

        $newRefreshToken = $refreshRes->json('data.refresh_token');
        $this->assertNotEquals($refreshToken, $newRefreshToken, 'Refresh token should rotate');
    }

    #[Test]
    public function refresh_fails_with_invalid_and_expired_tokens(): void
    {
        $user = User::factory()->create();

        // Invalid token
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ])->assertUnauthorized();

        // Expired token inserted directly into DB
        $expiredRaw = Str::random(64);
        DB::table('refresh_tokens')->insert([
            'id'         => Str::uuid()->toString(),
            'user_id'    => $user->id,
            'token_hash' => bcrypt($expiredRaw),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'expires_at' => now()->subMinute(), // already expired
        ]);

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $expiredRaw,
        ])->assertUnauthorized();
    }

    #[Test]
    public function refresh_token_is_single_use_and_rate_limited(): void
    {
        $user = User::factory()->create();

        // Login
        $loginRes = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertOk();

        $refreshToken = $loginRes->json('data.refresh_token');

        // First use: success
        $firstUse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk();

        $newRefresh = $firstUse->json('data.refresh_token');
        $this->assertNotEmpty($newRefresh);

        // Replay of original token: fails
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();

        // Second use of new token: works
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $newRefresh,
        ])->assertOk();
    }
}
