<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * This table represents the many-to-many relationship between
     * users and message threads. Each record represents a participant
     * in a thread, along with optional metadata like last_read_at.
     */
    public function up(): void {
        Schema::create('message_thread_user', function (Blueprint $table) {
            $table->id();

            // Foreign key to message_threads table
            $table->foreignId('message_thread_id')
                ->constrained('message_threads')
                ->cascadeOnDelete();

            // Foreign key to users table (UUID-based)
            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Timestamp for when the participant last read the thread
            $table->timestamp('last_read_at')->nullable();

            // Soft deletion for this pivot row (e.g. user left thread)
            $table->softDeletes();

            // Timestamps for created_at and updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('message_thread_user');
    }
};
