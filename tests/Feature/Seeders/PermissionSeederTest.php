<?php

namespace Tests\Feature\Seeders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Permission;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_permissions()
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_personal_dashboard',
            'description' => 'Allows access to the personal dashboard',
        ]);
    }
}
