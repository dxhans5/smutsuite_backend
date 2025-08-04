<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('creator_id');
            $table->uuid('client_id');
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime('requested_at');
            $table->string('booking_type')->default('chat');
            $table->string('status')->default('pending'); // pending, accepted, rejected, canceled
            $table->text('notes')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('booking_requests');
    }
};
