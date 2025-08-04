<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('public_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('tagline')->nullable();
            $table->json('pricing')->nullable(); // custom format
            $table->text('about')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->boolean('hide_from_locals')->default(false);
            $table->string('role')->nullable(); // e.g., 'creator', 'host'
            $table->string('location')->nullable(); // e.g., city or zip
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('public_profiles');
    }
};
