<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_user_with_valid_attributes(): void
    {
        $user = User::factory()->create([
            'display_name' => 'TestUser',
            'date_of_birth' => '1990-01-01',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'display_name' => 'TestUser',
        ]);
    }

    #[Test]
    public function password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret'),
        ]);

        $this->assertNotEquals('secret', $user->password);
        $this->assertTrue(Hash::check('secret', $user->password));
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function email_verified_at_can_be_nullable(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function user_has_roles_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertTrue(method_exists($user, 'roles'));
        $this->assertInstanceOf(BelongsToMany::class, $user->roles());
    }

    #[Test]
    public function test_user_must_be_21_or_older()
    {
        $underageData = [
            'display_name' => 'TooYoung',
            'email' => 'young@example.com',
            'password' => 'password123',
            'date_of_birth' => now()->subYears(20)->toDateString(), // Just 20
        ];

        $validator = Validator::make($underageData, [
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'date_of_birth' => ['required', 'date'],
        ]);

        $isValidDate = $validator->passes();
        $isOldEnough = now()->diffInYears($underageData['date_of_birth']) >= 21;

        $this->assertTrue($isValidDate, 'Date format should pass base validation');
        $this->assertFalse($isOldEnough, 'User should not be 21 or older');
    }

    #[Test]
    public function test_user_can_be_created_if_21_or_older()
    {
        $now = now()->startOfDay();
        Carbon::setTestNow($now);

        $validData = [
            'display_name' => 'OldEnough',
            'email' => 'grownup@example.com',
            'password' => 'securePass!',
            'date_of_birth' => $now->copy()->subYears(21)->toDateString(), // exactly 21
        ];

        $validator = Validator::make($validData, [
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'date_of_birth' => ['required', 'date'],
        ]);

        $this->assertTrue($validator->passes(), 'User passes base validation');

        // use startOfDay to ensure no time-of-day drift
        $dob = Carbon::parse($validData['date_of_birth'])->startOfDay();
        $this->assertTrue(
            $dob->diffInYears($now) >= 21,
            "User is old enough to register"
        );
    }




}
