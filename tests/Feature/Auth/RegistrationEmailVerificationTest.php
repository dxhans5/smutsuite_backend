<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Identity;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_sends_verification_email(): void
    {
        Notification::fake();

        $res = $this->postJson('/api/auth/register', [
            'email'                 => 'verifyme@example.com',
            'password'              => 'secret123!',
            'password_confirmation' => 'secret123!',
        ])->assertCreated();

        $user = User::query()->where('email', 'verifyme@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertNull($user->email_verified_at, 'User should start unverified');
    }

    #[Test]
    public function verify_email_with_signed_url_marks_user_verified(): void
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

        $this->actingAs($user)
            ->getJson($verificationUrl)
            ->assertRedirect(); // default behavior is redirect; adjust if API returns JSON

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        Event::assertDispatched(Verified::class);
    }

    #[Test]
    public function unverified_users_cannot_create_identities_but_verified_can(): void
    {
        $unverified = User::factory()->unverified()->create();
        $verified   = User::factory()->create(); // default factory verifies email

        // Unverified blocked
        $this->actingAs($unverified)
            ->postJson('/api/identities', [
                'type'   => 'creator',
                'label'  => 'My Persona',
                'status' => 'active',
            ])
            ->assertForbidden();

        // Verified allowed
        $this->actingAs($verified)
            ->postJson('/api/identities', [
                'type'   => 'creator',
                'label'  => 'My Persona',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('user_id', $verified->id);
    }

    #[Test]
    public function verified_user_can_set_active_identity_on_creation(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/api/identities', [
            'type'        => 'creator',
            'label'       => 'Alpha',
            'status'      => 'active',
            'set_active'  => true, // if your controller supports flag; otherwise set after
        ])->assertCreated();

        $identityId = $res->json('id');

        $user->refresh();
        $this->assertEquals($identityId, $user->active_identity_id);
    }
}
