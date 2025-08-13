<?php

namespace Database\Seeders;

use App\Models\Identity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a primary "Virus" user for development/local use.
 *
 * What this does:
 *  - Creates (or fetches) the user by unique email.
 *  - Ensures the user is email-verified.
 *  - Attaches the admin role if present (no duplicates).
 *  - Ensures the user has a default Identity using the NEW columns:
 *      - type      (NOT "role")
 *      - visibility (NOT "visibility_level")
 *    ...and sets it active. Any other identities are set inactive.
 *
 * Idempotent: safe to run repeatedly.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Create or fetch the user by unique key (email)
            $user = User::firstOrCreate(
                ['email' => 'dxhans5@gmail.com'], // lookup attributes
                [ // values to set on create only
                    'display_name'     => 'Virus',
                    'date_of_birth'    => '1977-06-02',
                    'password'         => Hash::make('H@nsen*1977'),
                    'email_verified_at'=> now(),
                ]
            );

            // 2) Attach admin role if it exists (RoleSeeder should have created it)
            if ($adminRole = Role::where('name', 'admin')->first()) {
                // prevents duplicate pivot rows, keeps existing ones
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            }

            // 3) Ensure a default Identity exists using the updated schema
            //    Columns: alias, type, label, visibility, verification_status, is_active
            $identity = $user->identities()->first();

            if (!$identity) {
                $alias = $this->uniqueAliasForUser('virus');

                $identity = Identity::create([
                    'id'                  => (string) Str::uuid(),
                    'user_id'             => $user->id,
                    'alias'               => $alias,
                    'type'                => 'user',      // canonical... not "role"
                    'label'               => 'Virus',
                    'visibility'          => 'public',    // not "visibility_level"
                    'verification_status' => 'pending',
                    'is_active'           => true,
                ]);
            }

            // 4) Make this identity the active one on the user, and
            //    ensure all other identities are inactive
            if (property_exists($user, 'active_identity_id') || $user->isFillable('active_identity_id')) {
                $user->active_identity_id = $identity->id;
                $user->save();
            }

            $user->identities()
                ->whereKeyNot($identity->id)
                ->update(['is_active' => false]);
        });
    }

    /**
     * Generate a unique, URL-safe alias.
     *
     * Uses a base slug and appends a numeric suffix when needed.
     * Alias is globally unique (matches your identities.alias unique index).
     */
    private function uniqueAliasForUser(string $base): string
    {
        $slug = Str::slug($base);
        if (!Identity::where('alias', $slug)->exists()) {
            return $slug;
        }

        $i = 2;
        while (Identity::where('alias', "{$slug}{$i}")->exists()) {
            $i++;
        }
        return "{$slug}{$i}";
    }
}
