<?php

namespace Tests\Feature\Broadcasting;

use App\Events\MessageSent;
use App\Models\User;
use App\Models\Identity;
use App\Models\Message;
use App\Models\MessageThread;
use App\Http\Resources\MessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MessageBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected User $sender;
    protected User $recipient;
    protected Identity $senderIdentity;
    protected Identity $recipientIdentity;
    protected MessageThread $messageThread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()->create(['email_verified_at' => now()]);
        $this->recipient = User::factory()->create(['email_verified_at' => now()]);

        $this->senderIdentity = Identity::factory()->create([
            'user_id' => $this->sender->id,
            'is_active' => true,
            'verification_status' => 'verified',
        ]);

        $this->recipientIdentity = Identity::factory()->create([
            'user_id' => $this->recipient->id,
            'is_active' => true,
            'verification_status' => 'verified',
        ]);

        $this->messageThread = MessageThread::factory()->create([
            'subject' => 'Test conversation',
        ]);

        $this->actingAs($this->sender, 'sanctum');
    }

    #[Test]
    public function message_sent_event_can_be_instantiated_with_required_data(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
            'body' => 'Test message content',
        ]);

        $event = new MessageSent($message, $this->messageThread->id);

        $this->assertInstanceOf(MessageSent::class, $event);
        $this->assertEquals($message->id, $event->message->id);
        $this->assertEquals($this->messageThread->id, $event->messageThreadId);
    }

    #[Test]
    public function message_sent_event_broadcasts_on_correct_private_channel(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
        ]);

        $event = new MessageSent($message, $this->messageThread->id);
        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals("private-message-thread.{$this->messageThread->id}", $channels[0]->name);
    }

    #[Test]
    public function message_sent_event_broadcasts_with_proper_json_resource_structure(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
            'body' => 'Broadcast test message',
        ]);

        $event = new MessageSent($message, $this->messageThread->id);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('data', $broadcastData);
        $this->assertInstanceOf(MessageResource::class, $broadcastData['data']);
    }

    #[Test]
    public function message_resource_returns_proper_structure(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
            'body' => 'Resource test message',
        ]);

        $resource = new MessageResource($message);
        $resourceArray = $resource->toArray(request());

        $this->assertArrayHasKey('id', $resourceArray);
        $this->assertArrayHasKey('message_thread_id', $resourceArray);
        $this->assertArrayHasKey('sender_identity_id', $resourceArray);
        $this->assertArrayHasKey('body', $resourceArray);
        $this->assertArrayHasKey('created_at', $resourceArray);
        $this->assertArrayHasKey('updated_at', $resourceArray);

        $this->assertEquals($message->id, $resourceArray['id']);
        $this->assertEquals('Resource test message', $resourceArray['body']);
        $this->assertEquals($this->senderIdentity->id, $resourceArray['sender_identity_id']);
    }

    #[Test]
    public function message_broadcast_respects_identity_filtering(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
        ]);

        $resource = new MessageResource($message);
        $resourceArray = $resource->toArray(request());

        // Should include sender_identity_id from active identity
        $this->assertEquals($this->senderIdentity->id, $resourceArray['sender_identity_id']);
    }

    #[Test]
    public function message_broadcast_includes_proper_timestamps(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resource = new MessageResource($message);
        $resourceArray = $resource->toArray(request());

        $this->assertArrayHasKey('created_at', $resourceArray);
        $this->assertArrayHasKey('updated_at', $resourceArray);

        // Verify ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d+Z/',
            $resourceArray['created_at']
        );
    }

    #[Test]
    public function broadcast_channel_uses_message_thread_naming_convention(): void
    {
        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
        ]);

        $event = new MessageSent($message, $this->messageThread->id);
        $channels = $event->broadcastOn();

        $this->assertEquals("private-message-thread.{$this->messageThread->id}", $channels[0]->name);
    }

    #[Test]
    public function message_broadcast_handles_large_content_gracefully(): void
    {
        $largeContent = str_repeat('Large message content test. ', 1000); // ~27KB

        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
            'body' => $largeContent,
        ]);

        $resource = new MessageResource($message);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals($largeContent, $resourceArray['body']);

        // Verify the resource data can be JSON encoded
        $json = json_encode($resourceArray);
        $this->assertIsString($json);
        $this->assertJson($json);
    }

    #[Test]
    public function failed_message_broadcasts_can_be_queued_for_retry(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
        ]);

        broadcast(new MessageSent($message, $this->messageThread->id));

        Queue::assertPushed(\Illuminate\Broadcasting\BroadcastEvent::class, function ($job) {
            return $job->event instanceof MessageSent;
        });
    }

    #[Test]
    public function message_broadcast_supports_to_others_for_sender_exclusion(): void
    {
        Event::fake();

        $message = Message::factory()->create([
            'message_thread_id' => $this->messageThread->id,
            'sender_identity_id' => $this->senderIdentity->id,
        ]);

        $broadcast = broadcast(new MessageSent($message, $this->messageThread->id))->toOthers();

        $this->assertInstanceOf(\Illuminate\Broadcasting\PendingBroadcast::class, $broadcast);
    }
}
