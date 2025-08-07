<?php

namespace Tests\Feature\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_the_virus_user_with_admin_role(): void
    {
        // Seed required roles first
        $this->seed(RoleSeeder::class);

        // Then seed the Virus user
        $this->seed(UserSeeder::class);

        // Fetch the Virus user and admin role
        $user = User::where('email', 'dxhans5@gmail.com')->first();
        $adminRole = Role::where('name', 'admin')->first();

        // Assert Virus user was seeded correctly
        $this->assertNotNull($user, 'Virus user was not seeded');
        $this->assertEquals('Virus', $user->display_name);
        $this->assertEquals('1977-06-02', \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d'));

        // Assert the admin role exists and is attached
        $this->assertNotNull($adminRole, 'Admin role was not seeded');
        $this->assertTrue(
            $user->roles->contains($adminRole),
            'Virus does not have the admin role'
        );
    }
}
