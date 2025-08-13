<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Important for Postgres + raw ALTER TABLE right after CREATE
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('alias')->unique();

            $table->string('type')->default('user');      // canonical: user, creator, service_provider, content_provider, host
            $table->string('label')->nullable();
            $table->string('visibility')->default('public');          // public | members | hidden
            $table->string('verification_status')->default('pending'); // pending | verified | rejected
            $table->string('avatar_path')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });

        // Postgres CHECK constraint - now safe because we're not in a transaction
        DB::statement("
            ALTER TABLE identities
            ADD CONSTRAINT identities_type_check
            CHECK (type IN ('user','creator','service_provider','content_provider','host'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('identities');
    }
};
