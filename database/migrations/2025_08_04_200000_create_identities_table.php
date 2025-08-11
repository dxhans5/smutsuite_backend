<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // UUID to match users.id (not foreignId)
            $table->uuid('user_id');

            $table->string('alias');
            $table->string('role'); // user, creator, host, service_provider, admin
            $table->string('visibility_level')->default('public');
            $table->string('verification_status')->default('pending');

            $table->uuid('payout_method_id')->nullable(); // Optional

            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'alias']);
            $table->index(['user_id', 'role']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Optional: enable if payout_methods.id is UUID
            // $table->foreign('payout_method_id')->references('id')->on('payout_methods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identities');
    }
};
