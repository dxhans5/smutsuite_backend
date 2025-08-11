<?php

namespace Tests\Feature\User;

use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Sequence;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\BookingRequest;
use App\Models\AvailabilityRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $client;
    protected Identity $creatorIdentity;
    protected Identity $clientIdentity;

    protected function setUp(): void {
        parent::setUp();

        $this->creator = User::factory()->create(['email_verified_at' => now()]);
        $this->client  = User::factory()->create(['email_verified_at' => now()]);

        $this->creatorIdentity = Identity::factory()->create([
            'user_id' => $this->creator->id,
            'role'    => 'creator',
            'is_active' => true,
        ]);
        $this->clientIdentity = Identity::factory()->create([
            'user_id' => $this->client->id,
            'role'    => 'user',
            'is_active' => true,
        ]);

        // make them the actual active identities used by the app
        $this->creator->active_identity_id = $this->creatorIdentity->id;
        $this->creator->save();

        $this->client->active_identity_id = $this->clientIdentity->id;
        $this->client->save();
    }

    #[Test]
    public function authenticated_user_can_update_their_availability(): void {
        $payload = [
            'availability' => [[
                'day_of_week'  => 1,
                'start_time'   => '10:00',
                'end_time'     => '14:00',
                'booking_type' => 'chat',
            ]],
        ];

        $this->actingAs($this->creator)
            ->putJson('/api/availability/me', $payload)
            ->assertOk()
            ->assertJsonPath('data.message', 'Availability updated.');

        $this->assertDatabaseHas('availability_rules', [
            'identity_id' => $this->creatorIdentity->id,
            'day_of_week' => 1,
        ]);
    }

    #[Test]
    public function user_can_view_their_own_availability(): void {
        AvailabilityRule::factory()
            ->count(2)
            ->state(new Sequence(
                [
                    'identity_id' => $this->creatorIdentity->id,
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '11:00',
                    'booking_type'=> 'chat',
                ],
                [
                    'identity_id' => $this->creatorIdentity->id,
                    'day_of_week' => 3,
                    'start_time'  => '13:00',
                    'end_time'    => '15:00',
                    'booking_type'=> 'chat',
                ],
            ))->create();

        $this->actingAs($this->creator)
            ->getJson('/api/availability/me')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function another_user_can_view_creator_availability(): void {
        AvailabilityRule::factory()->create([
            'identity_id' => $this->creatorIdentity->id,
            'is_active'   => true,
        ]);

        $this->actingAs($this->client)
            ->getJson('/api/availability/' . $this->creatorIdentity->id)
            ->assertOk()
            ->assertJsonFragment(['identity_id' => $this->creatorIdentity->id]);
    }

    #[Test]
    public function client_can_create_booking_request(): void {
        $payload = [
            'creator_identity_id' => $this->creatorIdentity->id,
            'requested_at'        => now()->addDays(1)->format('Y-m-d H:i:s'),
            'booking_type'        => 'chat',
            'notes'               => 'Please be gentle.',
            'timezone'            => 'America/New_York',
        ];

        $this->actingAs($this->client)
            ->postJson('/api/bookings', $payload)
            ->assertCreated()
            ->assertJsonFragment(['booking_type' => 'chat']);

        $this->assertDatabaseHas('booking_requests', [
            'creator_identity_id' => $this->creatorIdentity->id,
            'client_identity_id'  => $this->clientIdentity->id,
            'status'              => 'pending',
        ]);
    }

    #[Test]
    public function user_can_see_their_bookings(): void {
        BookingRequest::factory()->create([
            'creator_identity_id' => $this->creatorIdentity->id,
            'client_identity_id'  => $this->clientIdentity->id,
        ]);

        $this->actingAs($this->creator)
            ->getJson('/api/bookings/me')
            ->assertOk()
            ->assertJsonStructure(['as_creator', 'as_client']);
    }
}
