<?php

namespace Tests\Feature\Models;

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

    /**
     * Ensure a refresh token can be created and hashed properly.
     */
    #[Test]
    public function it_can_create_a_hashed_token(): void
    {
        $user = User::factory()->create();
        $plainToken = Str::random(64);

        $refreshToken = RefreshToken::factory()->create([
            'user_id'    => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $refreshToken->hash($plainToken);

        $this->assertDatabaseHas('refresh_tokens', [
            'id'      => $refreshToken->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            Hash::check($plainToken, $refreshToken->token_hash),
            'The hashed token should match the original'
        );
    }

    /**
     * Ensure a refresh token can be validated with the correct raw token.
     */
    #[Test]
    public function it_checks_token_validity(): void
    {
        $user = User::factory()->create();
        $rawToken = Str::random(64);

        $refreshToken = RefreshToken::create([
            'user_id'    => $user->id,
            'expires_at' => now()->addMinutes(10),
            'user_agent' => 'test-agent',
            'ip_address' => '127.0.0.1',
            'token_hash' => Hash::make($rawToken),
        ]);

        $matched = RefreshToken::unexpired()->get()
            ->first(fn ($t) => $t->matchesRawToken($rawToken));

        $this->assertInstanceOf(RefreshToken::class, $matched);
        $this->assertTrue($matched->is($refreshToken));
    }

    /**
     * Ensure matching fails for an invalid token.
     */
    #[Test]
    public function it_returns_null_for_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->assertNull(
            RefreshToken::unexpired()->get()
                ->first(fn ($t) => $t->matchesRawToken('invalid-token'))
        );
    }

    /**
     * Ensure matching fails for an expired token, even if the hash is valid.
     */
    #[Test]
    public function it_returns_null_for_expired_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()
            ->make(['user_id' => $user->id])
            ->hash($token);

        $refreshToken->expires_at = now()->subMinute();
        $refreshToken->save();

        $this->assertNull(
            RefreshToken::unexpired()->get()
                ->first(fn ($t) => $t->matchesRawToken($token))
        );
    }
}
