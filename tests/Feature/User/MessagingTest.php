<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected User $sender;
    protected User $recipient;

    protected function setUp(): void {
        parent::setUp();

        $this->sender = User::factory()->create(['email_verified_at' => now()]);
        $this->recipient = User::factory()->create(['email_verified_at' => now()]);
    }

    #[Test]
    public function user_can_start_a_message_thread(): void {
        $payload = [
            'recipient_id' => $this->recipient->id,
            'body' => 'Hello, let’s connect!',
        ];

        $this->actingAs($this->sender)
            ->postJson('/api/messages/send', $payload)
            ->assertCreated()
            ->assertJsonFragment(['body' => 'Hello, let’s connect!']);

        $this->assertDatabaseCount('message_threads', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    #[Test]
    public function user_can_fetch_their_threads(): void {
        $thread = MessageThread::factory()->create();
        $thread->participants()->attach([$this->sender->id, $this->recipient->id]);
        $thread->messages()->create([
            'sender_id' => $this->sender->id,
            'body' => 'Sample message',
        ]);

        $this->actingAs($this->sender)
            ->getJson('/api/messages/threads')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Sample message']);
    }

    #[Test]
    public function user_can_view_messages_in_a_thread(): void {
        $thread = MessageThread::factory()->create();
        $thread->participants()->attach([$this->sender->id, $this->recipient->id]);

        $thread->messages()->create([
            'sender_id' => $this->recipient->id,
            'body' => 'Welcome to my inbox',
        ]);

        $this->actingAs($this->sender)
            ->getJson("/api/messages/thread/{$thread->id}")
            ->assertOk()
            ->assertJsonFragment(['body' => 'Welcome to my inbox']);
    }

    #[Test]
    public function user_can_mark_thread_as_read(): void {
        $thread = MessageThread::factory()->create();
        $thread->participants()->attach([$this->sender->id, $this->recipient->id]);

        $this->actingAs($this->sender)
            ->postJson("/api/messages/{$thread->id}/read")
            ->assertOk()
            ->assertJson(['message' => 'Marked as read.']);

        $this->assertDatabaseHas('message_thread_user', [
            'message_thread_id' => $thread->id,
            'user_id' => $this->sender->id,
        ]);
    }

    #[Test]
    public function user_can_delete_their_own_message(): void {
        $thread = MessageThread::factory()->create();
        $thread->participants()->attach([$this->sender->id, $this->recipient->id]);

        $message = $thread->messages()->create([
            'sender_id' => $this->sender->id,
            'body' => 'This will be deleted.',
        ]);

        $this->actingAs($this->sender)
            ->deleteJson("/api/messages/{$message->id}")
            ->assertOk()
            ->assertJson(['message' => 'Deleted.']);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }
}
