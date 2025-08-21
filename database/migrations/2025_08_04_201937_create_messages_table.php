<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: messages
 *
 * Each row is a message posted by an Identity into a MessageThread.
 * We store:
 * - Which thread the message belongs to
 * - Which identity sent it
 * - The message body
 * - Timestamps and soft delete for audit and recovery
 *
 * Notes:
 * - Sender is identity-based (not user-based) to support multi-identity accounts.
 * - Indexes reflect common access patterns: by thread, by sender, by created_at.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Thread FK
            $table->foreignId('message_thread_id')
                ->constrained('message_threads')
                ->cascadeOnDelete();

            // Sender = Identity (UUID). This replaces legacy sender_id -> users.id
            $table->foreignUuid('sender_identity_id')
                ->constrained('identities')
                ->cascadeOnDelete();

            // Content
            $table->text('body');

            // Bookkeeping
            $table->timestamps();
            $table->softDeletes();

            // Query helpers
            $table->index('message_thread_id', 'msg_thread_idx');
            $table->index('sender_identity_id', 'msg_sender_identity_idx');
            $table->index('created_at', 'msg_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
