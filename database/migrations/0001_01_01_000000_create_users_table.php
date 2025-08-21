<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users, password reset tokens, and sessions.
     *
     * Notes
     * -----
     * - Users use UUID PKs (model should use HasUuids).
     * - We only INDEX `active_identity_id` here; the FK to identities is added
     *   in the identities migration (after that table exists).
     * - No self-FK for `invited_by_user_id` (kept as column + index to avoid PG quirk).
     */
    public function up(): void
    {
        /**
         * USERS
         * Account-level data only (public/creator presentation lives on Identity).
         */
        Schema::create('users', function (Blueprint $table) {
            // Primary key (UUID)
            $table->uuid('id')->primary();

            // Public-facing handle and login details
            $table->string('display_name', 40);          // Nickname/alias
            $table->date('date_of_birth');               // Age checks
            $table->string('email', 320)->unique();      // RFC-ish upper bound

            // Auth / account metadata
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');                  // Hashed
            $table->rememberToken();                     // "remember me"

            // Optional context
            $table->string('city', 120)->nullable();
            $table->boolean('has_completed_onboarding')->default(false);
            $table->string('onboarding_stage', 64)->nullable();

            // Referral / signup metadata
            $table->uuid('invited_by_user_id')->nullable();               // define column FIRST
            $table->index('invited_by_user_id', 'users_invited_by_idx');  // then index (no FK)
            $table->string('signup_context', 32)->nullable();             // e.g., 'google', 'direct'
            $table->string('google_id_token', 512)->nullable();           // opaque token if stored
            $table->ipAddress('initial_ip_address')->nullable();

            // Identity linkage (FK added later in identities migration)
            $table->uuid('active_identity_id')->nullable();
            $table->index('active_identity_id', 'users_active_identity_idx');

            // Timestamps & soft delete
            $table->timestamps();
            $table->softDeletes();

            // Helpful indexes for common lookups
            $table->index('display_name', 'users_display_name_idx');
            $table->index('city', 'users_city_idx');
        });

        /**
         * PASSWORD RESET TOKENS
         * Standard Laravel table for password resets.
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 320)->primary();
            $table->string('token');                     // store hashed token
            $table->timestamp('created_at')->nullable();
        });

        /**
         * SESSIONS
         * Used if Laravel’s database session driver is enabled.
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();                 // Session ID
            $table->foreignUuid('user_id')->nullable();      // Optional user
            $table->string('ip_address', 45)->nullable();    // IPv4/IPv6
            $table->text('user_agent')->nullable();
            $table->text('payload');                         // Serialized session
            $table->integer('last_activity')->index();

            // Fast lookups by user
            $table->index('user_id', 'sessions_user_idx');

            // Keep referential hygiene if user is deleted
            $table->foreign('user_id', 'sessions_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Drop sessions first (it depends on users)
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
