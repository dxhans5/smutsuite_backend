<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_personal_dashboard', 'description' => 'Allows access to the personal dashboard'],
            ['name' => 'edit_users', 'description' => 'Allows editing user accounts'],
            ['name' => 'manage_bookings', 'description' => 'Allows managing booking requests'],
            ['name' => 'send_messages', 'description' => 'Allows sending messages to other users'],
            ['name' => 'view_analytics', 'description' => 'Allows viewing platform analytics'],
            ['name' => 'manage_permissions', 'description' => 'Allows managing user permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }
    }
}
