<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the roles table.
     *
     * Each role defines a system-level capability grouping (e.g., Admin, Creator, User).
     * Roles can be assigned to users and/or identities depending on permission design.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name')->unique(); // Unique role name (e.g., 'Admin', 'User')
            $table->string('description')->nullable(); // Optional description for clarity
            $table->timestamps(); // created_at, updated_at

            // Index for faster name-based lookups (esp. in RBAC logic)
            $table->index('name');
        });
    }

    /**
     * Drop the roles table.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
