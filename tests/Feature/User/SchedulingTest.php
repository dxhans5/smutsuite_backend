<?php

namespace Tests\Feature\User;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\BookingRequest;
use App\Models\AvailabilityRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $client;

    protected function setUp(): void {
        parent::setUp();

        $this->creator = User::factory()->create(['email_verified_at' => now()]);
        $this->client = User::factory()->create(['email_verified_at' => now()]);
    }

    #[Test]
    public function authenticated_user_can_update_their_availability(): void {
        $payload = [
            'availability' => [[
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '14:00',
                'booking_type' => 'chat',
            ]],
        ];

        $this->actingAs($this->creator)
            ->putJson('/api/availability/me', $payload)
            ->assertOk()
            ->assertJson(['message' => 'Availability updated.']);

        $this->assertDatabaseHas('availability_rules', [
            'user_id' => $this->creator->id,
            'day_of_week' => 1,
        ]);
    }

    #[Test]
    public function user_can_view_their_own_availability(): void {
        AvailabilityRule::factory()->count(2)->create([
            'user_id' => $this->creator->id,
        ]);

        $this->actingAs($this->creator)
            ->getJson('/api/availability/me')
            ->assertOk()
            ->assertJsonCount(2);
    }

    #[Test]
    public function another_user_can_view_creator_availability(): void {
        AvailabilityRule::factory()->create([
            'user_id' => $this->creator->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->client)
            ->getJson('/api/availability/' . $this->creator->id)
            ->assertOk()
            ->assertJsonFragment(['user_id' => $this->creator->id]);
    }

    #[Test]
    public function client_can_create_booking_request(): void {
        $payload = [
            'creator_id' => $this->creator->id,
            'requested_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'booking_type' => 'chat',
            'notes' => 'Please be gentle.',
            'timezone' => 'America/New_York',
        ];

        $this->actingAs($this->client)
            ->postJson('/api/bookings', $payload)
            ->assertCreated()
            ->assertJsonFragment(['booking_type' => 'chat']);

        $this->assertDatabaseHas('booking_requests', [
            'creator_id' => $this->creator->id,
            'client_id' => $this->client->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function user_can_see_their_bookings(): void {
        BookingRequest::factory()->create([
            'creator_id' => $this->creator->id,
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->creator)
            ->getJson('/api/bookings/me')
            ->assertOk()
            ->assertJsonStructure(['as_creator', 'as_client']);
    }
}
