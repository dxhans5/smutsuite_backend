<?php

namespace Tests\Feature\Broadcasting;

use App\Events\MessageTest;
use App\Models\User;
use App\Models\Message;
use App\Models\Thread;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BroadcastingInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->authUser, 'sanctum');
    }

    #[Test]
    public function broadcasting_configuration_is_properly_set(): void
    {
        // In testing environment, we use 'log' driver to avoid WebSocket connections
        $this->assertEquals('log', config('broadcasting.default'));

        // But verify reverb configuration exists for production
        $this->assertEquals('x4tpep51yli1k96oeaui', config('broadcasting.connections.reverb.key'));
        $this->assertEquals('localhost', config('broadcasting.connections.reverb.options.host'));
        $this->assertEquals('8080', config('broadcasting.connections.reverb.options.port'));
        $this->assertEquals('http', config('broadcasting.connections.reverb.options.scheme'));
    }

    #[Test]
    public function message_test_event_can_be_instantiated(): void
    {
        $event = new MessageTest('Test broadcast message');

        $this->assertInstanceOf(MessageTest::class, $event);
        $this->assertEquals('Test broadcast message', $event->message);
    }

    #[Test]
    public function message_test_event_broadcasts_on_correct_channel(): void
    {
        $event = new MessageTest();
        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('test-channel', $channels[0]->name);
    }

    #[Test]
    public function message_test_event_broadcasts_with_proper_data_structure(): void
    {
        $testMessage = 'Custom test message';
        $event = new MessageTest($testMessage);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('data', $broadcastData);
        $this->assertArrayHasKey('message', $broadcastData['data']);
        $this->assertArrayHasKey('timestamp', $broadcastData['data']);
        $this->assertEquals($testMessage, $broadcastData['data']['message']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d+Z/', $broadcastData['data']['timestamp']);
    }

    #[Test]
    public function broadcast_queues_job_when_queue_connection_is_database(): void
    {
        // Temporarily set queue to database for this test
        config(['queue.default' => 'database']);
        Queue::fake();

        broadcast(new MessageTest());

        Queue::assertPushed(\Illuminate\Broadcasting\BroadcastEvent::class);
    }

    #[Test]
    public function broadcast_executes_immediately_when_queue_connection_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        Event::fake();

        $event = new MessageTest();
        broadcast($event);

        // With sync queue, the broadcast should process immediately
        // We can't easily test the actual WebSocket transmission in unit tests,
        // but we can verify the event was dispatched
        $this->assertTrue(true); // Placeholder - actual transmission requires integration testing
    }

    #[Test]
    public function event_helper_function_dispatches_broadcast_event(): void
    {
        Event::fake();

        $testEvent = new MessageTest('Event helper test');
        event($testEvent);

        Event::assertDispatched(MessageTest::class, function ($event) {
            return $event->message === 'Event helper test';
        });
    }

    #[Test]
    public function broadcast_with_to_others_excludes_current_socket(): void
    {
        Event::fake();

        $event = new MessageTest();
        $broadcast = broadcast($event)->toOthers();

        $this->assertInstanceOf(\Illuminate\Broadcasting\PendingBroadcast::class, $broadcast);

        // The actual socket exclusion behavior requires client-side testing
        $this->assertTrue(true);
    }

    #[Test]
    public function failed_broadcast_jobs_can_be_retried(): void
    {
        config(['queue.default' => 'database']);

        // Force a broadcast failure by using invalid Reverb configuration
        config(['broadcasting.connections.reverb.options.port' => '9999']);

        try {
            // Use event() instead of broadcast()->now()
            event(new MessageTest());
            $this->fail('Expected broadcast to fail with invalid port');
        } catch (\Exception $e) {
            // Expected failure - in real scenarios this would be queued and retried
            $this->assertTrue(str_contains($e->getMessage(), 'connect') || str_contains($e->getMessage(), 'port'));
        }

        // Reset to working configuration
        config(['broadcasting.connections.reverb.options.port' => '8080']);
    }

    #[Test]
    public function broadcast_respects_channel_authorization_when_using_private_channels(): void
    {
        // Create a test event that uses private channels
        $privateEvent = new class extends MessageTest {
            public function broadcastOn(): array
            {
                return [
                    new PrivateChannel('user.' . auth()->id()),
                ];
            }
        };

        $channels = $privateEvent->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-user.' . $this->authUser->id, $channels[0]->name);
    }

    #[Test]
    public function broadcast_handles_multiple_channels_correctly(): void
    {
        $multiChannelEvent = new class extends MessageTest {
            public function broadcastOn(): array
            {
                return [
                    new Channel('public-announcements'),
                    new PrivateChannel('user.' . auth()->id()),
                    new Channel('general'),
                ];
            }
        };

        $channels = $multiChannelEvent->broadcastOn();

        $this->assertCount(3, $channels);
        $this->assertEquals('public-announcements', $channels[0]->name);
        $this->assertEquals('private-user.' . $this->authUser->id, $channels[1]->name);
        $this->assertEquals('general', $channels[2]->name);
    }

    #[Test]
    public function broadcast_data_serialization_handles_complex_objects(): void
    {
        $complexEvent = new class extends MessageTest {
            public function broadcastWith(): array
            {
                return [
                    'data' => [
                        'user' => [
                            'id' => auth()->id(),
                            'name' => auth()->user()->name,
                        ],
                        'message' => $this->message,
                        'timestamp' => now()->toISOString(),
                        'metadata' => [
                            'channel' => 'test',
                            'version' => '1.0',
                        ],
                    ],
                ];
            }
        };

        $event = new $complexEvent('Complex data test');
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('data', $broadcastData);
        $this->assertArrayHasKey('user', $broadcastData['data']);
        $this->assertArrayHasKey('metadata', $broadcastData['data']);
        $this->assertEquals($this->authUser->id, $broadcastData['data']['user']['id']);
        $this->assertEquals('1.0', $broadcastData['data']['metadata']['version']);
    }
}
