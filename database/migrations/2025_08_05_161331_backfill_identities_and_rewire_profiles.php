<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add identity_id columns to profiles (nullable for backfill window)
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable()->after('id');
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable()->after('id');
        });

        // 2) Create a default identity for each user (if none) and map profiles
        DB::transaction(function () {
            // NOTE: users has 'display_name' (not 'name')
            $users = DB::table('users')->select('id', 'display_name', 'email', 'active_identity_id')->get();

            foreach ($users as $user) {
                $identityId = $user->active_identity_id;

                if (!$identityId) {
                    $identityId = (string) Str::uuid();
                    $alias = $user->display_name ?: (string) Str::of($user->email)->before('@');

                    DB::table('identities')->insert([
                        'id' => $identityId,
                        'user_id' => $user->id,            // UUID FK
                        'alias' => $alias,
                        'role'  => 'user',                 // safe default; can be updated later
                        'visibility_level' => 'public',
                        'verification_status' => 'pending',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('users')->where('id', $user->id)->update([
                        'active_identity_id' => $identityId,
                    ]);
                }

                // Map existing profiles for this user to the new/default identity
                DB::table('public_profiles')
                    ->where('user_id', $user->id)
                    ->update(['identity_id' => $identityId]);

                DB::table('private_profiles')
                    ->where('user_id', $user->id)
                    ->update(['identity_id' => $identityId]);
            }
        });

        // 3) Add FKs and make NOT NULL; then drop old user_id columns
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });

        // If doctrine/dbal is not installed, replace ->change() with a temp column flow.
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable(false)->change();
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->uuid('identity_id')->nullable(false)->change();
        });

        // Drop legacy user_id FKs & columns (they are UUIDs in your schema)
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        // Re-add user_id (UUID) and backfill from identities.user_id, then drop identity_id

        Schema::table('public_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('id');
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('id');
        });

        // Backfill user_id from the identity owner
        DB::statement("
            UPDATE public_profiles pp
            SET user_id = i.user_id
            FROM identities i
            WHERE pp.identity_id = i.id
        ");

        DB::statement("
            UPDATE private_profiles pp
            SET user_id = i.user_id
            FROM identities i
            WHERE pp.identity_id = i.id
        ");

        Schema::table('public_profiles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Make NOT NULL again (if DBAL present)
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });

        // Drop the identity_id FK + column
        Schema::table('public_profiles', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropColumn('identity_id');
        });
        Schema::table('private_profiles', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropColumn('identity_id');
        });
    }
};
