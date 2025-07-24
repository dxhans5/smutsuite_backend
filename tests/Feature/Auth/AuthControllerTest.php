<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
            'date_of_birth' => now()->subYears(25),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => __('auth.failed'),
            ]);
    }

    public function test_login_fails_if_required_fields_are_missing()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email-format',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_respects_localization()
    {
        App::setLocale('de');

        $response = $this->withHeaders(['Accept-Language' => 'de'])
            ->postJson('/api/login', [
                'email' => '',
                'password' => '',
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Das E-Mail Feld ist erforderlich', $response->json('errors.email')[0]);

    }

    public function test_login_rejects_underage_user()
    {
        $user = User::factory()->create([
            'email' => 'underage@example.com',
            'password' => Hash::make('password123'),
            'date_of_birth' => now()->subYears(20),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'underage@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'success' => false,
                'message' => __('auth.underage'),
            ]);
    }

    #[Test]
    public function test_logout_revokes_token_and_returns_localized_message(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('auth.logged_out'),
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
        ]);
    }

    #[Test]
    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertUnauthorized()
            ->assertJson([
                'message' => __('auth.unauthenticated'),
            ]);
    }
}
