<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('creator_identity_id');
            $table->uuid('client_identity_id');

            $table->foreign('creator_identity_id')->references('id')->on('identities')->cascadeOnDelete();
            $table->foreign('client_identity_id')->references('id')->on('identities')->cascadeOnDelete();

            $table->dateTime('requested_at');
            $table->string('booking_type')->default('chat');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();

            $table->index('creator_identity_id');
            $table->index('client_identity_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('booking_requests');
    }
};
