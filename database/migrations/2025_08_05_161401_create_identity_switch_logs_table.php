<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('identity_switch_logs', function (Blueprint $table) {
            $table->id();

            // FIX: UUID to match users.id
            $table->uuid('user_id');

            $table->uuid('from_identity_id')->nullable();
            $table->uuid('to_identity_id');
            $table->string('ip')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('from_identity_id')->references('id')->on('identities')->nullOnDelete();
            $table->foreign('to_identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_switch_logs');
    }
};
