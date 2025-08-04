<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('private_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->json('notes')->nullable();
            $table->json('journal')->nullable();
            $table->json('favorite_kinks')->nullable();
            $table->string('mood')->nullable();
            $table->string('emotional_state')->nullable();
            $table->string('timezone')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('private_profiles');
    }
};
