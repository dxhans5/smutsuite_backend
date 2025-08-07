<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the 'message_threads' table, which represents a conversation
     * between multiple participants. Each thread may optionally have a subject/title.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('subject')->nullable()->comment('Optional title or subject of the message thread');
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes()->comment('Soft delete support for threads');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
