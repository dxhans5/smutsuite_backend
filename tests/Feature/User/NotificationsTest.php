<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function user_can_receive_a_notification(): void
    {
        $payload = [
            'user_id' => $this->user->id,
            'message' => 'New booking request received.',
            'type' => 'booking',
            'action_url' => 'https://example.com/bookings/1',
        ];

        $this->actingAs($this->user)
            ->postJson('/api/notifications/send', $payload)
            ->assertOk()
            ->assertJson(['message' => 'Notification sent.']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => User::class,
        ]);
    }

    #[Test]
    public function user_can_fetch_notifications(): void
    {
        DatabaseNotification::insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\Notifications\GenericNotification',
                'notifiable_id' => $this->user->id,
                'notifiable_type' => User::class,
                'data' => json_encode([
                    'message' => 'Test message 1',
                    'type' => 'info',
                    'action_url' => 'https://example.com/1',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\Notifications\GenericNotification',
                'notifiable_id' => $this->user->id,
                'notifiable_type' => User::class,
                'data' => json_encode([
                    'message' => 'Test message 2',
                    'type' => 'info',
                    'action_url' => 'https://example.com/2',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'notifications');
    }

    #[Test]
    public function user_can_mark_notification_as_read(): void
    {
        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\GenericNotification',
            'notifiable_id' => $this->user->id,
            'notifiable_type' => User::class,
            'data' => json_encode([
                'message' => 'Read me',
                'type' => 'alert',
                'action_url' => 'https://example.com',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJson(['message' => 'Notification marked as read.']);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
