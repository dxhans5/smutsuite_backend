<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test the registration process and email verification flow.
 *
 * Verifies:
 * - Email verification notification is sent on registration
 * - Signed verification link correctly verifies the user
 * - Only verified users can create identities
 * - Verified users can immediately activate a new identity
 */
class RegistrationEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a verification email is sent after registration
     * and the user is unverified until they act on it.
     */
    #[Test]
    public function registration_sends_verification_email(): void
    {
        Notification::fake();

        $res = $this->postJson('/api/auth/register', [
            'email'                 => 'verifyme@example.com',
            'password'              => 'secret123!',
            'password_confirmation' => 'secret123!',
            'display_name'          => 'VerificationTester',
            'date_of_birth'         => '1990-01-01',
            'role'                  => 'user',
        ])->assertCreated();

        $user = User::where('email', 'verifyme@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
        $this->assertNull($user->email_verified_at, 'User should start unverified');
    }


    /**
     * Test that the signed email verification URL successfully verifies the user.
     */
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
            [
                'id'   => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );

        $this->actingAs($user)
            ->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('message', __('auth.verification_success'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        Event::assertDispatched(Verified::class);
    }

    /**
     * Test that only verified users are allowed to create identities.
     * Unverified users should be blocked.
     */
    #[Test]
    public function unverified_users_cannot_create_identities_but_verified_can(): void
    {
        $unverified = User::factory()->unverified()->create();
        $verified   = User::factory()->create(); // default factory = verified

        // Unverified: should get 403 Forbidden
        $this->actingAs($unverified)
            ->postJson('/api/identities', [
                'type'   => 'creator',
                'label'  => 'My Persona',
                'status' => 'active',
            ])
            ->assertForbidden();

        // Verified: should succeed
        $this->actingAs($verified)
            ->postJson('/api/identities', [
                'alias'  => 'myalias',
                'role'   => 'creator',
                'type'   => 'creator',
                'label'  => 'My Persona',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $verified->id);
    }

    /**
     * Test that a verified user can immediately set a newly created identity as active
     * by passing `set_active: true` in the request payload.
     */
    #[Test]
    public function verified_user_can_set_active_identity_on_creation(): void
    {
        $user = User::factory()->create(); // Verified by default

        $res = $this->actingAs($user)->postJson('/api/identities', [
            'type'       => 'creator',
            'label'      => 'Alpha',
            'alias'      => 'alpha',          // Required
            'role'       => 'provider',           // Required
            'status'     => 'active',
            'set_active' => true,             // Optional flag to activate on creation
        ])->assertCreated();

        $identityId = $res->json('id');

        $user->refresh();
        $this->assertEquals($identityId, $user->active_identity_id);
    }

}
