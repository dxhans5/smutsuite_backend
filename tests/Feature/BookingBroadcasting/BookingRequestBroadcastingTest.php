<?php

namespace Tests\Feature\BookingBroadcasting;

use App\Events\BookingRequestStatusChanged;
use App\Models\User;
use App\Models\Identity;
use App\Models\BookingRequest;
use App\Http\Resources\BookingRequestResource;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BookingRequestBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $client;
    protected Identity $creatorIdentity;
    protected Identity $clientIdentity;
    protected BookingRequest $bookingRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create(['email_verified_at' => now()]);
        $this->client = User::factory()->create(['email_verified_at' => now()]);

        $this->creatorIdentity = Identity::factory()->create([
            'user_id' => $this->creator->id,
            'is_active' => true,
            'verification_status' => 'verified',
        ]);

        $this->clientIdentity = Identity::factory()->create([
            'user_id' => $this->client->id,
            'is_active' => true,
            'verification_status' => 'verified',
        ]);

        $this->bookingRequest = BookingRequest::factory()->create([
            'creator_identity_id' => $this->creatorIdentity->id,
            'client_identity_id' => $this->clientIdentity->id,
            'booking_type' => BookingType::CONSULTATION,
            'status' => BookingStatus::PENDING,
        ]);

        $this->actingAs($this->creator, 'sanctum');
    }

    #[Test]
    public function booking_request_status_changed_event_can_be_instantiated(): void
    {
        $event = new BookingRequestStatusChanged($this->bookingRequest, 'pending');

        $this->assertInstanceOf(BookingRequestStatusChanged::class, $event);
        $this->assertEquals($this->bookingRequest->id, $event->bookingRequest->id);
        $this->assertEquals('pending', $event->previousStatus);
    }

    #[Test]
    public function booking_request_status_changed_broadcasts_on_correct_channels(): void
    {
        $event = new BookingRequestStatusChanged($this->bookingRequest, 'pending');
        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertCount(3, $channels);

        foreach ($channels as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel);
        }

        $expectedChannels = [
            "private-booking-request.{$this->bookingRequest->id}",
            "private-creator-bookings.{$this->creatorIdentity->id}",
            "private-client-bookings.{$this->clientIdentity->id}",
        ];

        $actualChannels = array_map(fn($channel) => $channel->name, $channels);

        foreach ($expectedChannels as $expected) {
            $this->assertContains($expected, $actualChannels);
        }
    }

    #[Test]
    public function booking_request_status_changed_broadcasts_with_proper_resource_structure(): void
    {
        $event = new BookingRequestStatusChanged($this->bookingRequest, 'pending');
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('data', $broadcastData);
        $this->assertArrayHasKey('previous_status', $broadcastData);
        $this->assertInstanceOf(BookingRequestResource::class, $broadcastData['data']);
        $this->assertEquals('pending', $broadcastData['previous_status']);
    }

    #[Test]
    public function booking_request_resource_returns_proper_structure(): void
    {
        $resource = new BookingRequestResource($this->bookingRequest);
        $resourceArray = $resource->toArray(request());

        $this->assertArrayHasKey('id', $resourceArray);
        $this->assertArrayHasKey('creator_identity_id', $resourceArray);
        $this->assertArrayHasKey('client_identity_id', $resourceArray);
        $this->assertArrayHasKey('requested_at', $resourceArray);
        $this->assertArrayHasKey('booking_type', $resourceArray);
        $this->assertArrayHasKey('status', $resourceArray);
        $this->assertArrayHasKey('notes', $resourceArray);
        $this->assertArrayHasKey('timezone', $resourceArray);
        $this->assertArrayHasKey('created_at', $resourceArray);
        $this->assertArrayHasKey('updated_at', $resourceArray);

        $this->assertEquals($this->bookingRequest->id, $resourceArray['id']);
        $this->assertEquals('consultation', $resourceArray['booking_type']);
        $this->assertEquals('pending', $resourceArray['status']);
    }

    #[Test]
    public function booking_request_update_status_triggers_broadcast_event(): void
    {
        Event::fake();

        $result = $this->bookingRequest->updateStatus(BookingStatus::CONFIRMED);

        $this->assertTrue($result);
        $this->assertEquals(BookingStatus::CONFIRMED, $this->bookingRequest->fresh()->status);

        Event::assertDispatched(BookingRequestStatusChanged::class, function ($event) {
            return $event->bookingRequest->id === $this->bookingRequest->id &&
                $event->previousStatus === 'pending';
        });
    }

    #[Test]
    public function booking_request_update_status_validates_transitions(): void
    {
        // Invalid transition: pending -> completed (should go through confirmed first)
        $result = $this->bookingRequest->updateStatus(BookingStatus::COMPLETED);

        $this->assertFalse($result);
        $this->assertEquals(BookingStatus::PENDING, $this->bookingRequest->fresh()->status);
    }

    #[Test]
    public function booking_request_broadcasts_can_be_queued_for_retry(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        broadcast(new BookingRequestStatusChanged($this->bookingRequest, 'pending'));

        Queue::assertPushed(\Illuminate\Broadcasting\BroadcastEvent::class, function ($job) {
            return $job->event instanceof BookingRequestStatusChanged;
        });
    }

    #[Test]
    public function booking_request_resource_handles_enum_casting(): void
    {
        $this->bookingRequest->update([
            'booking_type' => BookingType::VIRTUAL_SESSION,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $resource = new BookingRequestResource($this->bookingRequest);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals('virtual_session', $resourceArray['booking_type']);
        $this->assertEquals('confirmed', $resourceArray['status']);
    }

    #[Test]
    public function booking_request_timestamps_are_iso_formatted(): void
    {
        $resource = new BookingRequestResource($this->bookingRequest);
        $resourceArray = $resource->toArray(request());

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d+Z/',
            $resourceArray['created_at']
        );

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d+Z/',
            $resourceArray['requested_at']
        );
    }
}
