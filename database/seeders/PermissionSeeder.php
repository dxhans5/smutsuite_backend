<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => 'view_personal_dashboard',
        ], [
            'description' => 'Allows access to the personal dashboard',
        ]);
    }
}
