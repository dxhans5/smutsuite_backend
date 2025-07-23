<?php

namespace Tests\Feature\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_the_virus_user_with_admin_role(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $user = User::where('email', 'dxhans5@gmail.com')->first();
        $adminRole = Role::where('name', 'admin')->first();

        $this->assertNotNull($user, 'Virus user was not seeded');
        $this->assertEquals('Virus', $user->display_name);
        $this->assertEquals('1977-06-02', $user->date_of_birth->format('Y-m-d'));

        $this->assertNotNull($adminRole, 'Admin role was not seeded');
        $this->assertTrue($user->roles->contains($adminRole), 'Virus does not have the admin role');
    }
}
