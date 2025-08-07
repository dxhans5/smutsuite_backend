<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------------------------
        // 1) AVAILABILITY (user -> identity)
        // ---------------------------
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable()->after('id');
            // Index early for backfill performance
            $table->index('identity_id', 'availability_rules_identity_id_idx');
        });

        // Backfill identity_id from users.active_identity_id
        // Postgres syntax
        DB::statement("
            UPDATE availability_rules ar
            SET identity_id = u.active_identity_id
            FROM users u
            WHERE ar.user_id = u.id
        ");

        // Add FK, make NOT NULL, drop old user_id & its FK
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });

        // Make NOT NULL (requires doctrine/dbal). If you don't have it, see comments.
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable(false)->change();
        });

        Schema::table('availability_rules', function (Blueprint $table) {
            // Legacy FK was created via $table->foreign('user_id')->references('id')->on('users')
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // ---------------------------
        // 2) BOOKINGS (user -> identity)
        // ---------------------------
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->uuid('creator_identity_id')->nullable()->after('id');
            $table->uuid('client_identity_id')->nullable()->after('creator_identity_id');
            $table->index(['creator_identity_id', 'client_identity_id'], 'booking_requests_identity_ids_idx');
        });

        // Backfill from users.active_identity_id
        DB::statement("
            UPDATE booking_requests br
            SET creator_identity_id = u1.active_identity_id,
                client_identity_id  = u2.active_identity_id
            FROM users u1, users u2
            WHERE br.creator_id = u1.id
              AND br.client_id  = u2.id
        ");

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->foreign('creator_identity_id')->references('id')->on('identities')->cascadeOnDelete();
            $table->foreign('client_identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->uuid('creator_identity_id')->nullable(false)->change();
            $table->uuid('client_identity_id')->nullable(false)->change();
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['creator_id','client_id']);
        });

        // ---------------------------
        // 3) MESSAGING PARTICIPANTS (user -> identity)
        // ---------------------------
        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable()->after('message_thread_id');
            $table->index('identity_id', 'message_thread_user_identity_id_idx');
        });

        DB::statement("
            UPDATE message_thread_user mtu
            SET identity_id = u.active_identity_id
            FROM users u
            WHERE mtu.user_id = u.id
        ");

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable(false)->change();
        });

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // ---------------------------
        // 4) MESSAGES (user -> identity)
        // ---------------------------
        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_identity_id')->nullable()->after('message_thread_id');
            $table->index('sender_identity_id', 'messages_sender_identity_id_idx');
        });

        DB::statement("
            UPDATE messages m
            SET sender_identity_id = u.active_identity_id
            FROM users u
            WHERE m.sender_id = u.id
        ");

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('sender_identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_identity_id')->nullable(false)->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
        });
    }

    public function down(): void
    {
        // Reverse of the above. We restore *_user columns and drop *_identity columns.
        // Because data loss is possible (identities may not map 1:1 anymore), this is best-effort.

        // 4) MESSAGES
        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_id')->nullable()->after('message_thread_id');
        });

        // Map identities back to users (owner of identity)
        DB::statement("
            UPDATE messages m
            SET sender_id = i.user_id
            FROM identities i
            WHERE m.sender_identity_id = i.id
        ");

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_id')->nullable(false)->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_identity_id']);
            $table->dropIndex('messages_sender_identity_id_idx');
            $table->dropColumn('sender_identity_id');
        });

        // 3) MESSAGE_THREAD_USER
        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('message_thread_id');
        });

        DB::statement("
            UPDATE message_thread_user mtu
            SET user_id = i.user_id
            FROM identities i
            WHERE mtu.identity_id = i.id
        ");

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });

        Schema::table('message_thread_user', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropIndex('message_thread_user_identity_id_idx');
            $table->dropColumn('identity_id');
        });

        // 2) BOOKINGS
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->uuid('creator_id')->nullable()->after('id');
            $table->uuid('client_id')->nullable()->after('creator_id');
        });

        DB::statement("
            UPDATE booking_requests br
            SET creator_id = i1.user_id,
                client_id  = i2.user_id
            FROM identities i1, identities i2
            WHERE br.creator_identity_id = i1.id
              AND br.client_identity_id  = i2.id
        ");

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->uuid('creator_id')->nullable(false)->change();
            $table->uuid('client_id')->nullable(false)->change();
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropForeign(['creator_identity_id']);
            $table->dropForeign(['client_identity_id']);
            $table->dropIndex('booking_requests_identity_ids_idx');
            $table->dropColumn(['creator_identity_id','client_identity_id']);
        });

        // 1) AVAILABILITY
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('id');
        });

        DB::statement("
            UPDATE availability_rules ar
            SET user_id = i.user_id
            FROM identities i
            WHERE ar.identity_id = i.id
        ");

        Schema::table('availability_rules', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('availability_rules', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });

        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropIndex('availability_rules_identity_id_idx');
            $table->dropColumn('identity_id');
        });
    }
};
