<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // one per Identity
            $table->foreignUuid('identity_id')
                ->constrained('identities')
                ->cascadeOnDelete()
                ->unique();

            // Stripe account reference (nullable until created)
            $table->string('stripe_account_id')->unique()->nullable();

            // Simple lifecycle flags
            // not_onboarded | pending | active | restricted
            $table->string('status')->default('not_onboarded');
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('details_submitted')->default(false);
            $table->timestamp('requirements_due_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};
