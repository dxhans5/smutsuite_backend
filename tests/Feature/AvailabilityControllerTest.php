<?php

namespace Tests\Feature;

use App\Events\AvailabilityUpdated;
use App\Models\AvailabilityRule;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Identity $identity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Deactivate any existing identities created by the user factory
        Identity::where('user_id', $this->user->id)->update(['is_active' => false]);

        // Create the test identity as the only active one
        $this->identity = Identity::factory()->for($this->user)->create(['is_active' => true]);

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function user_can_list_their_availability_rules(): void
    {
        $availabilityRule1 = AvailabilityRule::factory()->for($this->identity)->create([
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);

        $availabilityRule2 = AvailabilityRule::factory()->for($this->identity)->create([
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '16:00'
        ]);

        $response = $this->getJson('/api/availability');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'day_of_week', 'start_time', 'end_time', 'booking_type', 'is_available']
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_can_create_availability_rule_and_broadcasts_update(): void
    {
        Event::fake();

        $availabilityData = [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'booking_type' => 'consultation',
            'is_available' => true
        ];

        $response = $this->postJson('/api/availability', $availabilityData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'day_of_week', 'start_time', 'end_time', 'booking_type', 'is_available']
            ]);

        $this->assertDatabaseHas('availability_rules', [
            'identity_id' => $this->identity->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'booking_type' => 'consultation'
        ]);

        Event::assertDispatched(AvailabilityUpdated::class, function ($event) {
            return $event->creatorIdentity->id === $this->identity->id
                && $event->updateType === 'schedule_changed'
                && $event->availabilityRule !== null;
        });
    }

    #[Test]
    public function create_availability_rule_validates_required_fields(): void
    {
        $response = $this->postJson('/api/availability', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week', 'start_time', 'end_time', 'booking_type']);
    }

    #[Test]
    public function create_availability_rule_validates_time_format(): void
    {
        $response = $this->postJson('/api/availability', [
            'day_of_week' => 1,
            'start_time' => 'invalid-time',
            'end_time' => '25:00',
            'booking_type' => 'consultation'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    #[Test]
    public function create_availability_rule_validates_end_time_after_start_time(): void
    {
        $response = $this->postJson('/api/availability', [
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '09:00',
            'booking_type' => 'consultation'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    #[Test]
    public function user_can_update_availability_rule_and_broadcasts_update(): void
    {
        Event::fake();

        $availabilityRule = AvailabilityRule::factory()->for($this->identity)->create();

        $updateData = [
            'start_time' => '10:00',
            'end_time' => '18:00',
            'is_available' => false
        ];

        $response = $this->putJson("/api/availability/{$availabilityRule->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('availability_rules', [
            'id' => $availabilityRule->id,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_available' => false
        ]);

        Event::assertDispatched(AvailabilityUpdated::class, function ($event) use ($availabilityRule) {
            return $event->creatorIdentity->id === $this->identity->id
                && $event->updateType === 'schedule_changed'
                && $event->availabilityRule->id === $availabilityRule->id;
        });
    }

    #[Test]
    public function user_can_delete_availability_rule_and_broadcasts_update(): void
    {
        Event::fake();

        $availabilityRule = AvailabilityRule::factory()->for($this->identity)->create();

        $response = $this->deleteJson("/api/availability/{$availabilityRule->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('availability_rules', ['id' => $availabilityRule->id]);

        Event::assertDispatched(AvailabilityUpdated::class, function ($event) {
            return $event->creatorIdentity->id === $this->identity->id
                && $event->updateType === 'schedule_changed'
                && $event->availabilityRule === null;
        });
    }

    #[Test]
    public function user_can_update_online_status_and_broadcasts_update(): void
    {
        Event::fake();

        $response = $this->postJson('/api/availability/status', [
            'status' => 'online'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['identity_id', 'status', 'updated_at']
            ])
            ->assertJson([
                'data' => [
                    'identity_id' => $this->identity->id,
                    'status' => 'online'
                ]
            ]);

        Event::assertDispatched(AvailabilityUpdated::class, function ($event) {
            return $event->creatorIdentity->id === $this->identity->id
                && $event->updateType === 'went_online'
                && $event->availabilityRule === null;
        });
    }

    #[Test]
    public function user_can_go_offline_and_broadcasts_update(): void
    {
        Event::fake();

        $response = $this->postJson('/api/availability/status', [
            'status' => 'offline'
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(AvailabilityUpdated::class, function ($event) {
            return $event->creatorIdentity->id === $this->identity->id
                && $event->updateType === 'went_offline';
        });
    }

    #[Test]
    public function status_update_validates_allowed_statuses(): void
    {
        $response = $this->postJson('/api/availability/status', [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_availability_endpoints(): void
    {
        // Reset authentication instead of calling logout()
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/availability')->assertStatus(401);
        $this->postJson('/api/availability', [])->assertStatus(401);
        $this->postJson('/api/availability/status', [])->assertStatus(401);
    }

    #[Test]
    public function user_cannot_access_other_users_availability_rules(): void
    {
        $otherUser = User::factory()->create();
        $otherIdentity = Identity::factory()->for($otherUser)->create(['is_active' => true]);
        $otherAvailabilityRule = AvailabilityRule::factory()->for($otherIdentity)->create();

        $response = $this->putJson("/api/availability/{$otherAvailabilityRule->id}", [
            'start_time' => '10:00'
        ]);

        $response->assertStatus(403);
    }
}
