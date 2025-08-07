<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Creates the `refresh_tokens` table to support secure, hashed
     * refresh token storage for session management and rotation.
     *
     * Columns:
     * - id: Primary UUID for traceability and logging
     * - user_id: FK reference to the user who owns the token
     * - token_hash: Hashed refresh token (SHA256 or similar)
     * - user_agent: Optional browser/client fingerprint
     * - ip_address: Optional IP to support abuse detection
     * - expires_at: When the token should be invalidated
     * - revoked_at: If rotation or logout has revoked this token
     * - timestamps: created_at / updated_at for trace/debug/auditing
     */
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Link to owning user
            $table->foreignUuid('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Secure, hashed token (raw token never stored)
            $table->string('token_hash')->unique();

            // Metadata for abuse detection and analytics
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();

            // Lifecycle control
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();

            // Auditing
            $table->timestamps();

            // Composite index for efficient token cleanup/validation
            $table->index(['user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
