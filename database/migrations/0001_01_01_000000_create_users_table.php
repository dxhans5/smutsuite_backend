<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Users Table
         * Stores account-level data and public profile info.
         */
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID primary key (set via Laravel HasUuids)
            $table->string('display_name', 40); // Public-facing nickname/alias
            $table->date('date_of_birth'); // Used for 21+ verification
            $table->string('email')->unique(); // Login + email communication
            $table->timestamp('email_verified_at')->nullable(); // Set when email is verified
            $table->string('password'); // Hashed password
            $table->string('city')->nullable(); // Optional city info for user context
            $table->enum('role', ['user', 'creator', 'service_provider', 'host']); // Initial signup role
            $table->boolean('has_completed_onboarding')->default(false); // Tracks onboarding completion
            $table->string('onboarding_stage')->nullable(); // Current stage if onboarding not complete
            $table->uuid('invited_by_user_id')->nullable(); // Optional referral user
            $table->string('signup_context')->nullable(); // E.g. 'google', 'direct', etc.
            $table->string('google_id_token')->nullable(); // For Google OAuth signups
            $table->ipAddress('initial_ip_address')->nullable(); // IP during registration
            $table->rememberToken(); // Token for "remember me" logins
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft deletes
        });

        /**
         * Password Reset Tokens Table
         * Used for password reset flow (token validation).
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // User email
            $table->string('token'); // Hashed token
            $table->timestamp('created_at')->nullable(); // Token issued time
        });

        /**
         * Sessions Table (optional)
         * Used by Laravel's session driver if configured.
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID
            $table->foreignUuid('user_id')->nullable()->index(); // Related user (if logged in)
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6 support
            $table->text('user_agent')->nullable(); // Browser/Device info
            $table->text('payload'); // Session contents
            $table->integer('last_activity')->index(); // Timestamp for activity tracking
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
