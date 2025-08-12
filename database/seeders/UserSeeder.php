<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    public function run(): void {
        $virus = User::firstOrCreate(
            [
                'email' => 'dxhans5@gmail.com',
                'display_name' => 'Virus',
                'date_of_birth' => '1977-06-02',
                'password' => Hash::make('H@nsen*1977'),
                'email_verified_at' => now(),
                'role' => 'user',
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$virus->roles()->where('role_id', $adminRole->id)->exists()) {
            $virus->roles()->attach($adminRole->id);
        }

        if (isset($virus->active_identity_id)) {
            // make every non-active identity inactive
            $virus->identities()
                ->whereKeyNot($virus->active_identity_id)
                ->update(['is_active' => false]);
        }
    }
}
