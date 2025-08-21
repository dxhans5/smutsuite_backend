<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: public_profiles
 *
 * Public-facing profile for a single Identity.
 * One-to-one: each identity may have at most one public profile.
 *
 * Fields:
 * - identity_id (UUID FK) ... owner identity
 * - display_name, avatar_url, tagline, about ... presentation
 * - pricing (JSON) ... flexible pricing structure
 * - is_visible, hide_from_locals ... visibility controls
 * - type (string) ... role/category like 'creator', 'client', 'host'
 * - location (string) ... free-form location text
 *
 * Indexes:
 * - unique(identity_id) ... enforce one profile per identity
 * - is_visible, type ... common filters
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_profiles', function (Blueprint $table) {
            $table->id();

            // Owner identity (UUID FK)
            $table->foreignUuid('identity_id')
                ->constrained('identities')
                ->cascadeOnDelete();

            // Presentation
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('tagline')->nullable();
            $table->text('about')->nullable();

            // Flexible pricing blob
            $table->json('pricing')->nullable();

            // Visibility controls
            $table->boolean('is_visible')->default(false);
            $table->boolean('hide_from_locals')->default(false);

            // Identity role/category
            $table->string('type', 32)->nullable(); // e.g. 'creator', 'client', 'host'

            // Free-form location
            $table->string('location')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Constraints & indexes
            $table->unique('identity_id', 'public_profiles_identity_unique');
            $table->index('is_visible', 'public_profiles_visible_idx');
            $table->index('type', 'public_profiles_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_profiles');
    }
};
