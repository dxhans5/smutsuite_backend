<?php

namespace Tests\Unit\Hashing;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefreshTokenHasherTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_hashes_and_verifies_refresh_tokens(): void
    {
        $raw = Str::random(64);
        $hash = Hash::make($raw);

        $this->assertTrue(Hash::check($raw, $hash));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_to_verify_wrong_token(): void
    {
        $raw = Str::random(64);
        $wrong = Str::random(64);
        $hash = Hash::make($raw);

        $this->assertFalse(Hash::check($wrong, $hash));
    }
}
