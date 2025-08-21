<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Postgres runs migrations inside a transaction by default.
     * Leave this FALSE only if you add statements that cannot run in a txn
     * (e.g., CREATE INDEX CONCURRENTLY). Our current checks are txn-safe,
     * so this can be true or false. Keeping as-is per your style.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        /**
         * Table: identities
         *
         * One user can own multiple identities (creator, host, etc.).
         * - UUID PK for global uniqueness
         * - alias is globally unique (public handle)
         * - status/visibility fields are constrained via CHECKs (Postgres)
         */
        Schema::create('identities', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Owner: users.id (UUID) — cascade so deleting a user removes their identities
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Public handle / alias — globally unique
            $table->string('alias', 40)->unique('identities_alias_unique');

            // Classification & metadata
            // See CHECK constraints below for allowed values
            $table->string('type', 32)->default('user');               // user|creator|service_provider|content_provider|host
            $table->string('label', 64)->nullable();                   // free-form badge/label
            $table->string('visibility', 16)->default('public');       // public|members|hidden
            $table->string('verification_status', 16)->default('pending'); // pending|verified|rejected

            $table->string('avatar_path')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            // Query helpers
            $table->index('user_id', 'identities_user_idx');
            $table->index(['user_id', 'is_active'], 'identities_user_active_idx');
            $table->index('type', 'identities_type_idx');
            $table->index('visibility', 'identities_visibility_idx');
            $table->index('verification_status', 'identities_verif_idx');
        });

        // --- Postgres CHECK constraints to lock allowed values ---
        DB::statement("
            ALTER TABLE identities
            ADD CONSTRAINT identities_type_check
            CHECK (type IN ('user','creator','service_provider','content_provider','host'))
        ");

        DB::statement("
            ALTER TABLE identities
            ADD CONSTRAINT identities_visibility_check
            CHECK (visibility IN ('public','members','hidden'))
        ");

        DB::statement("
            ALTER TABLE identities
            ADD CONSTRAINT identities_verification_status_check
            CHECK (verification_status IN ('pending','verified','rejected'))
        ");

        /**
         * Add FK from users.active_identity_id → identities.id
         * Do this AFTER identities exists (keeps your edited-originals rule).
         */
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('active_identity_id', 'users_active_identity_fk')
                ->references('id')->on('identities')
                ->nullOnDelete(); // if an identity is deleted, clear the pointer
        });
    }

    public function down(): void
    {
        // Drop the FK from users before dropping identities (required in Postgres)
        Schema::table('users', function (Blueprint $table) {
            // Use the explicit name we set above for clarity
            try {
                $table->dropForeign('users_active_identity_fk');
            } catch (\Throwable $e) {
                // Fallback: drop by column array if name differs locally
                try { $table->dropForeign(['active_identity_id']); } catch (\Throwable $e2) {}
            }
        });

        Schema::dropIfExists('identities');
    }
};
