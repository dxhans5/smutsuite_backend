<?php

namespace Tests\Unit\Models;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_hashed_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $refreshToken->hash($token); // this sets token_hash and saves

        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $refreshToken->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue(Hash::check($token, $refreshToken->token_hash));
    }

    #[Test]
    public function it_checks_token_validity(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
            'user_agent' => 'test-agent',
            'ip_address' => '127.0.0.1',
            'token_hash' => Hash::make($token),
        ]);

        $found = RefreshToken::unexpired()->get()
            ->first(fn ($t) => $t->matchesRawToken($token));

        $this->assertInstanceOf(RefreshToken::class, $found);
        $this->assertTrue($found->is($refreshToken));
    }

    #[Test]
    public function it_returns_null_for_invalid_token(): void
    {
        $user = User::factory()->create();

        $found = RefreshToken::unexpired()->get()
            ->first(fn ($t) => $t->matchesRawToken('invalid-token'));

        $this->assertNull($found);
    }

    #[Test]
    public function it_returns_null_for_expired_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(64);

        $refreshToken = RefreshToken::factory()
            ->make(['user_id' => $user->id])
            ->hash($token);

        $refreshToken->expires_at = Carbon::now()->subMinute();
        $refreshToken->save();

        $this->assertNull(RefreshToken::unexpired()->get()
            ->first(fn ($t) => $t->matchesRawToken($token)));
    }
}
