<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('identity_id');
            $table->foreign('identity_id')
                ->references('id')->on('identities')
                ->cascadeOnDelete();

            $table->tinyInteger('day_of_week'); // 0 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('booking_type')->default('chat'); // chat, call, in_person
            $table->boolean('is_available')->default(true); // Fixed field name
            $table->timestamps();

            // helpful indexes
            $table->index(['identity_id', 'day_of_week']);
            $table->unique(['identity_id', 'day_of_week', 'start_time', 'end_time']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('availability_rules');
    }
};
