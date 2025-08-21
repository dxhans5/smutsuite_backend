<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: private_profiles
 *
 * Per-Identity private data (not per User).
 * One-to-one: exactly one row per identity (enforced by UNIQUE).
 * JSONB columns are used for structured, flexible blobs.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('private_profiles', function (Blueprint $table) {
            $table->id();

            // Owner identity (UUID FK)
            $table->foreignUuid('identity_id')
                ->constrained('identities')
                ->cascadeOnDelete();

            // -------- Profile payload --------
            // Use JSONB on Postgres for efficient containment/keys queries.
            $table->jsonb('notes')->nullable();           // arbitrary key/value notes
            $table->jsonb('journal')->nullable();         // dated entries, etc.
            $table->jsonb('favorite_kinks')->nullable();  // array of strings
            $table->jsonb('custom_fields')->nullable();   // free-form structured fields

            // Light scalar fields (fast filters)
            $table->string('mood', 64)->nullable();
            $table->string('emotional_state', 64)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->timestamps();

            // Exactly one profile per identity
            $table->unique('identity_id', 'private_profiles_identity_unique');

            // Optional: if you frequently query by presence of JSON keys, add GIN indexes:
            // $table->index(['notes'], 'pp_notes_gin')->algorithm('gin');        // requires manual SQL on PG
            // $table->index(['journal'], 'pp_journal_gin')->algorithm('gin');
            // (If you want these, we can add DB::statement() with CREATE INDEX ... USING GIN)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_profiles');
    }
};
