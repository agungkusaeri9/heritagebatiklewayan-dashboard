<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_passes_roles_and_filters_by_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::factory()->create(['name' => 'Super Admin User', 'username' => 'super_user']);
        $superAdmin->assignRole('super admin');

        $admin = User::factory()->create(['name' => 'Admin Staff', 'username' => 'admin_staff']);
        $admin->assignRole('admin');

        $customerOne = User::factory()->create(['name' => 'Siti Pelanggan', 'username' => 'siti_cust']);
        $customerOne->assignRole(Role::findByName('customer', 'api'));

        $customerTwo = User::factory()->create(['name' => 'Andi Pelanggan', 'username' => 'andi_cust']);
        $customerTwo->assignRole(Role::findByName('customer', 'api'));

        $this->actingAs($superAdmin)
            ->get('/dashboard/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->has('roles', 4)
                ->has('users.data', 4)
            );

        $this->actingAs($superAdmin)
            ->get('/dashboard/users?role=admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->where('filters.role', 'admin')
                ->has('users.data', 1)
                ->where('users.data.0.username', 'admin_staff')
            );

        $this->actingAs($superAdmin)
            ->get('/dashboard/users?role=customer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->where('filters.role', 'customer')
                ->has('users.data', 2)
            );

        $this->actingAs($superAdmin)
            ->get('/dashboard/users?role=customer&search=Siti')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->where('filters.role', 'customer')
                ->where('filters.search', 'Siti')
                ->has('users.data', 1)
                ->where('users.data.0.username', 'siti_cust')
            );

        $this->actingAs($superAdmin)
            ->get('/dashboard/users?role=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->has('users.data', 4)
            );
    }
}
