<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Events\Verified;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

class RegistrationEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        // Freeze time for consistent age validation
        Carbon::setTestNow(Carbon::parse(('2025-07-25 12:00:00')));

        // Ensure required roles exist in test DB
        Role::create([
            'name' => 'user',
            'description' => 'Default user role',
        ]);
    }
    #[Test]
    public function it_sends_email_verification_notification_on_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'display_name'   => 'Blayze Test',
            'email'          => 'blayze@example.com',
            'password'       => 'testpassword123',
            'date_of_birth'  => '1995-07-24',
            'role'           => 'user',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'blayze@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function it_verifies_email_with_valid_link(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'verifyme@example.com',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->getJson($verificationUrl);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);

        Event::assertDispatched(Verified::class);
    }

    #[Test]
    public function it_rejects_verification_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'badhash@example.com',
        ]);

        $badUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1('wrong@example.com')]
        );

        $response = $this->actingAs($user)->getJson($badUrl);

        $response->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_requires_authentication_for_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $this->getJson($url)->assertUnauthorized();
    }

    #[Test]
    public function it_rejects_expired_verification_link(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'expired@example.com',
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(5), // Already expired
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->getJson($expiredUrl);

        $response->assertStatus(403); // Laravel throws InvalidSignatureException
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_fails_with_invalid_email_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1('tampered@example.com')]
        );

        $response = $this->actingAs($user)->getJson($url);

        $response->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_does_nothing_if_already_verified(): void
    {
        $user = User::factory()->create(); // already verified

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk(); // Still a valid response
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_allows_reuse_of_link_if_not_verified_yet(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->getJson($url);
        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_does_not_resend_email_if_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/email/resend');

        $response->assertStatus(400);
        Notification::assertNothingSent();
    }
}
