<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder {
    public function run(): void {
        DB::table('roles')->truncate();

        $roles = [
            ['name' => 'user', 'description' => 'Standard user'],
            ['name' => 'content_provider', 'description' => 'Performer or creator'],
            ['name' => 'service_provider', 'description' => 'Offering sessions or services'],
            ['name' => 'host', 'description' => 'Hosting events or parties'],
            ['name' => 'admin', 'description' => 'Platform administrator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
