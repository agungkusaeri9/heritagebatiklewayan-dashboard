<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_seeder_creates_only_one_customer_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $customerRoles = Role::query()->where('name', 'customer')->get();

        $this->assertCount(1, $customerRoles);
        $this->assertSame('api', $customerRoles->first()?->guard_name);
    }

    public function test_roles_table_enforces_unique_role_names_across_guards(): void
    {
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->expectException(QueryException::class);

        Role::create(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_role_store_validation_rejects_duplicate_customer_role(): void
    {
        Role::create(['name' => 'super admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super admin');
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $response = $this->actingAs($admin)->post('/dashboard/roles', [
            'name' => 'customer',
            'permissions' => [],
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Role::query()->where('name', 'customer')->count());
    }

    public function test_admin_can_assign_customer_api_role_when_creating_and_updating_users(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super admin');

        $createResponse = $this->actingAs($admin)->post('/dashboard/users', [
            'name' => 'Budi Pelanggan',
            'username' => 'budi_pelanggan',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'roles' => ['customer'],
        ]);

        $createResponse->assertRedirect('/dashboard/users');

        $createdUser = User::where('username', 'budi_pelanggan')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('customer', 'api'));

        $updateResponse = $this->actingAs($admin)->patch("/dashboard/users/{$createdUser->id}", [
            'name' => 'Budi Updated',
            'username' => 'budi_pelanggan',
            'email' => 'budi@example.com',
            'password' => '',
            'roles' => ['customer'],
        ]);

        $updateResponse->assertRedirect('/dashboard/users');
        $this->assertTrue($createdUser->fresh()->hasRole('customer', 'api'));
    }

    public function test_admin_role_excludes_restricted_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $adminRole = Role::findByName('admin', 'web');

        $restrictedPermissions = [
            'view admin dashboard',
            'view orders',
            'edit orders',
            'update order status',
            'update order payment',
            'update order shipping',
            'view users',
            'create users',
            'edit users',
            'view payment methods',
            'view app configs',
            'create app configs',
            'edit app configs',
            'delete app configs',
            'view store settings',
            'edit store settings',
            'view activity logs',
        ];

        foreach ($restrictedPermissions as $permission) {
            $this->assertFalse(
                $adminRole->hasPermissionTo($permission),
                "Expected admin role NOT to have permission [{$permission}]."
            );
        }

        $this->assertTrue($adminRole->hasPermissionTo('view dashboard'));
        $this->assertTrue($adminRole->hasPermissionTo('view stores'));
        $this->assertTrue($adminRole->hasPermissionTo('view customers'));
        $this->assertTrue($adminRole->hasPermissionTo('view products'));
        $this->assertTrue($adminRole->hasPermissionTo('view product virtual try ons'));

        $adminStoreRole = Role::findByName('admin store', 'web');
        $this->assertTrue($adminStoreRole->hasPermissionTo('view dashboard'));
        $this->assertFalse($adminStoreRole->hasPermissionTo('view admin dashboard'));
    }
}
