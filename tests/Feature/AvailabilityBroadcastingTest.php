<?php

namespace Tests\Feature;

use App\Events\AvailabilityUpdated;
use App\Models\Identity;
use App\Models\AvailabilityRule;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function availability_updated_event_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();
        $availabilityRule = AvailabilityRule::factory()->for($identity, 'identity')->create();

        $event = new AvailabilityUpdated($identity, $availabilityRule, 'schedule_changed');

        $this->assertInstanceOf(AvailabilityUpdated::class, $event);
        $this->assertEquals($identity->id, $event->creatorIdentity->id);
        $this->assertEquals($availabilityRule->id, $event->availabilityRule->id);
        $this->assertEquals('schedule_changed', $event->updateType);
    }

    #[Test]
    public function availability_updated_event_broadcasts_on_correct_channels(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();
        $availabilityRule = AvailabilityRule::factory()->for($identity, 'identity')->create();

        $event = new AvailabilityUpdated($identity, $availabilityRule);
        $channels = $event->broadcastOn();

        $this->assertCount(3, $channels);
        $this->assertContainsOnlyInstancesOf(Channel::class, $channels);

        $channelNames = array_map(fn($channel) => $channel->name, $channels);

        $this->assertContains("private-identity.{$identity->id}", $channelNames);
        $this->assertContains('discovery', $channelNames);
        $this->assertContains("private-availability.{$identity->id}", $channelNames);
    }

    #[Test]
    public function availability_updated_event_includes_correct_broadcast_data(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create(['alias' => 'TestCreator']);
        $availabilityRule = AvailabilityRule::factory()->for($identity, 'identity')->create();

        $event = new AvailabilityUpdated($identity, $availabilityRule, 'online_status_changed');
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('creator_identity_id', $broadcastData);
        $this->assertArrayHasKey('creator_name', $broadcastData);
        $this->assertArrayHasKey('update_type', $broadcastData);
        $this->assertArrayHasKey('availability_rule', $broadcastData);
        $this->assertArrayHasKey('timestamp', $broadcastData);

        $this->assertEquals($identity->id, $broadcastData['creator_identity_id']);
        $this->assertEquals('TestCreator', $broadcastData['creator_name']);
        $this->assertEquals('online_status_changed', $broadcastData['update_type']);
        $this->assertIsArray($broadcastData['availability_rule']);
    }

    #[Test]
    public function availability_updated_event_handles_null_availability_rule(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();

        $event = new AvailabilityUpdated($identity, null, 'went_offline');
        $broadcastData = $event->broadcastWith();

        $this->assertNull($broadcastData['availability_rule']);
        $this->assertEquals('went_offline', $broadcastData['update_type']);
    }

    #[Test]
    public function availability_updated_event_uses_correct_broadcast_name(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();

        $event = new AvailabilityUpdated($identity);

        $this->assertEquals('availability.updated', $event->broadcastAs());
    }

    #[Test]
    public function availability_updated_event_can_be_fired(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();
        $availabilityRule = AvailabilityRule::factory()->for($identity, 'identity')->create();

        event(new AvailabilityUpdated($identity, $availabilityRule));

        Event::assertDispatched(AvailabilityUpdated::class);
    }

    #[Test]
    public function multiple_availability_events_can_be_handled(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $identity1 = Identity::factory()->for($user)->create();
        $identity2 = Identity::factory()->for($user)->create();

        event(new AvailabilityUpdated($identity1, null, 'went_online'));
        event(new AvailabilityUpdated($identity2, null, 'went_offline'));

        Event::assertDispatched(AvailabilityUpdated::class, 2);
    }

    #[Test]
    public function availability_update_types_are_properly_differentiated(): void
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->for($user)->create();

        $updateTypes = ['schedule_changed', 'went_online', 'went_offline', 'booking_slot_opened', 'booking_slot_filled'];

        foreach ($updateTypes as $updateType) {
            $event = new AvailabilityUpdated($identity, null, $updateType);
            $this->assertEquals($updateType, $event->updateType);
        }
    }

    #[Test]
    public function discovery_channel_receives_all_availability_updates(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $identity1 = Identity::factory()->for($user1)->create();
        $identity2 = Identity::factory()->for($user2)->create();

        $event1 = new AvailabilityUpdated($identity1, null, 'went_online');
        $event2 = new AvailabilityUpdated($identity2, null, 'schedule_changed');

        $channels1 = $event1->broadcastOn();
        $channels2 = $event2->broadcastOn();

        $channelNames1 = array_map(fn($channel) => $channel->name, $channels1);
        $channelNames2 = array_map(fn($channel) => $channel->name, $channels2);

        $this->assertContains('discovery', $channelNames1);
        $this->assertContains('discovery', $channelNames2);
    }
}
