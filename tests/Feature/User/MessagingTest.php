<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Identity;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for SmutSuite user messaging system.
 *
 * Covers:
 * - Starting a message thread
 * - Fetching user's threads
 * - Viewing messages in a thread
 * - Marking a thread as read
 * - Deleting user's own messages
 */
class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected User $sender;
    protected User $recipient;

    /**
     * Set up test users and active identities.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()
            ->has(Identity::factory(['is_active' => true]), 'identities')
            ->create(['email_verified_at' => now()]);

        $this->recipient = User::factory()
            ->has(Identity::factory(['is_active' => true]), 'identities')
            ->create(['email_verified_at' => now()]);

        // Ensure identity is saved and assigned
        $this->sender->refresh();
        $this->recipient->refresh();

        $this->assertDatabaseHas('users', ['id' => $this->recipient->id]);
        $this->assertNotNull($this->recipient->active_identity_id);

        $this->sender->update([
            'active_identity_id' => $this->sender->identities->first()->id,
        ]);

        $this->recipient->update([
            'active_identity_id' => $this->recipient->identities->first()->id,
        ]);
    }


    /**
     * Ensure a user can start a new message thread with a recipient.
     */
    #[Test]
    public function user_can_start_a_message_thread(): void
    {
        $payload = [
            'recipient_id' => $this->recipient->active_identity_id,
            'body'         => 'Hello, let’s connect!',
        ];

        $this->actingAs($this->sender)
            ->postJson('/api/messages/send', $payload)
            ->assertCreated()
            ->assertJsonFragment(['body' => 'Hello, let’s connect!']);

        $this->assertDatabaseCount('message_threads', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    /**
     * Ensure a user can fetch their message threads.
     */
    #[Test]
    public function user_can_fetch_their_threads(): void
    {
        $thread = MessageThread::factory()->create();

        $thread->participants()->attach([
            $this->sender->active_identity_id,
            $this->recipient->active_identity_id,
        ]);

        $thread->messages()->create([
            'sender_identity_id' => $this->sender->active_identity_id,
            'body'               => 'Sample message',
        ]);

        $this->actingAs($this->sender)
            ->getJson('/api/messages/threads')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Sample message']);
    }

    /**
     * Ensure a user can view messages within a specific thread.
     */
    #[Test]
    public function user_can_view_messages_in_a_thread(): void
    {
        $thread = MessageThread::factory()->create();

        $thread->participants()->attach([
            $this->sender->active_identity_id,
            $this->recipient->active_identity_id,
        ]);

        $thread->messages()->create([
            'sender_identity_id' => $this->recipient->active_identity_id,
            'body'               => 'Welcome to my inbox',
        ]);

        $this->actingAs($this->sender)
            ->getJson("/api/messages/thread/{$thread->id}")
            ->assertOk()
            ->assertJsonFragment(['body' => 'Welcome to my inbox']);
    }

    /**
     * Ensure a user can mark a thread as read.
     */
    #[Test]
    public function user_can_mark_thread_as_read(): void
    {
        $thread = MessageThread::factory()->create();

        $thread->participants()->attach([
            $this->sender->active_identity_id,
            $this->recipient->active_identity_id,
        ]);

        $this->actingAs($this->sender)
            ->postJson("/api/messages/{$thread->id}/read")
            ->assertOk()
            ->assertJson(['message' => 'Marked as read.']);

        $this->assertDatabaseHas('message_thread_user', [
            'message_thread_id' => $thread->id,
            'identity_id'       => $this->sender->active_identity_id,
        ]);
    }

    /**
     * Ensure a user can delete a message they sent.
     */
    #[Test]
    public function user_can_delete_their_own_message(): void
    {
        $thread = MessageThread::factory()->create();

        $thread->participants()->attach([
            $this->sender->active_identity_id,
            $this->recipient->active_identity_id,
        ]);

        $message = $thread->messages()->create([
            'sender_identity_id' => $this->sender->active_identity_id,
            'body'               => 'This will be deleted.',
        ]);

        $this->actingAs($this->sender)
            ->deleteJson("/api/messages/{$message->id}")
            ->assertOk()
            ->assertJson(['message' => 'Deleted.']);

        $this->assertSoftDeleted('messages', [
            'id' => $message->id,
        ]);
    }
}
