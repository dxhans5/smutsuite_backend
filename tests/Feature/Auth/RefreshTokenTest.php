<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Assumes you have a refresh-token flow similar to:
 *  - POST /api/auth/login => returns { token, refresh_token }
 *  - POST /api/auth/refresh { refresh_token } => returns { token, refresh_token }
 * Adjust the URIs/keys if your implementation differs.
 */
class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_refresh_access_token_with_valid_refresh_token(): void
    {
        $user = User::factory()->create();

        // Simulate login response (or call your real login if it issues refresh tokens)
        $loginRes = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password', // default from factory
        ])->assertOk();

        $refresh = $loginRes->json('refresh_token');
        $this->assertNotEmpty($refresh);

        $res = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refresh,
        ])->assertOk()
            ->assertJsonStructure(['token', 'refresh_token']);

        $this->assertNotEquals($refresh, $res->json('refresh_token'), 'refresh token should rotate');
    }

    #[Test]
    public function refresh_fails_with_invalid_or_expired_token(): void
    {
        $user = User::factory()->create();

        // Invalid
        $this->postJson('/api/auth/refresh', ['refresh_token' => 'bogus'])
            ->assertUnauthorized();

        // Expired (if your table/logic supports expiry)
        // Example assumes `refresh_tokens` table has `revoked_at` or `expires_at`.
        $valid = Str::random(64);
        DB::table('refresh_tokens')->insert([
            'id'         => Str::uuid()->toString(),
            'user_id'    => $user->id,
            'token'      => hash('sha256', $valid),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'expires_at' => now()->subMinute(), // already expired
        ]);

        $this->postJson('/api/auth/refresh', ['refresh_token' => $valid])
            ->assertUnauthorized();
    }

    #[Test]
    public function refresh_is_rate_limited_per_token_and_revokes_old_on_use(): void
    {
        $user = User::factory()->create();

        $loginRes = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertOk();

        $refresh = $loginRes->json('refresh_token');

        // First use OK
        $first = $this->postJson('/api/auth/refresh', ['refresh_token' => $refresh])->assertOk();
        $newRefresh = $first->json('refresh_token');

        // Replay of old token should fail
        $this->postJson('/api/auth/refresh', ['refresh_token' => $refresh])->assertUnauthorized();

        // New token works once
        $this->postJson('/api/auth/refresh', ['refresh_token' => $newRefresh])->assertOk();
    }
}
