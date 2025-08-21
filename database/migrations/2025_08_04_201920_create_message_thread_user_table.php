<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot: message_thread_user
 *
 * Represents participants (by Identity) in a message thread.
 * Each row = one identity participating in one thread, with optional metadata.
 *
 * Design notes:
 * - Threads are identity-based (not user-based).
 * - Composite uniqueness prevents duplicate participants in a thread.
 * - Soft deletes allow “leaving” a thread without destroying history.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_thread_user', function (Blueprint $table) {
            $table->id();

            // FK -> message_threads.id
            $table->foreignId('message_thread_id')
                ->constrained('message_threads')
                ->cascadeOnDelete();

            // FK -> identities.id (UUID)
            // Using foreignUuid keeps it explicit for Postgres UUID columns.
            $table->foreignUuid('identity_id')
                ->constrained('identities')
                ->cascadeOnDelete();

            // When this participant last read the thread
            $table->timestamp('last_read_at')->nullable();

            // Soft delete participant membership without losing audit history
            $table->softDeletes();

            // created_at / updated_at
            $table->timestamps();

            // Prevent duplicate participation rows for the same identity in the same thread
            $table->unique(['message_thread_id', 'identity_id'], 'mtu_thread_identity_unique');

            // Helpful indexes for common queries:
            // - whereHas('participants', fn($q) => $q->where('identity_id', ...))
            // - latest unread checks filtering by identity_id
            $table->index('identity_id', 'mtu_identity_idx');
            $table->index('message_thread_id', 'mtu_thread_idx');
            $table->index('last_read_at', 'mtu_last_read_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_thread_user');
    }
};
