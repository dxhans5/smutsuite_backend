<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the predefined roles for the SmutSuite platform.
 *
 * These roles represent the primary identity groupings and platform access levels.
 * This seeder can be safely re-run as it uses `firstOrCreate` for idempotency.
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Optional: clear roles table if you're not relying on soft deletes or production safety
        DB::table('roles')->truncate();

        $roles = [
            [
                'name'        => 'user',
                'description' => 'Standard user',
            ],
            [
                'name'        => 'creator',
                'description' => 'Performer or content creator',
            ],
            [
                'name'        => 'provider',
                'description' => 'Offers direct services or sessions',
            ],
            [
                'name'        => 'host',
                'description' => 'Hosts events, parties, or gatherings',
            ],
            [
                'name'        => 'admin',
                'description' => 'Platform administrator with full access',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
