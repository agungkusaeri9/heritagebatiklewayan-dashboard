<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Seed permissions first
        $this->call(PermissionSeeder::class);

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Super Admin role and sync all permissions
        $superAdminRole = Role::findOrCreate('super admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        // Create Admin role and assign operational permissions
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminPermissions = [
            'view dashboard',
            'view customers',
            'edit customers',
            'view products',
            'filter products by store',
            'create products',
            'edit products',
            'import products',
            'view categories',
            'create categories',
            'edit categories',
            'view variant types',
            'create variant types',
            'edit variant types',
            'view banners',
            'create banners',
            'edit banners',
            'toggle banners',
            'view couriers',
            'view locations',
            'view notifications',
            'create notifications',
            'manage notification settings',
            'view product reviews',
            'toggle product reviews',
            'view stores',
            'create stores',
            'edit stores',
            'delete stores',
            'view privacy policy',
            'edit privacy policy',
            'view landing page settings',
            'edit landing page settings',
            'view faqs',
            'create faqs',
            'edit faqs',
            'view app versions',
            'create app versions',
            'edit app versions',
            'delete app versions',
            'view virtual try on models',
            'create virtual try on models',
            'edit virtual try on models',
            'delete virtual try on models',
            'view product virtual try ons',
            'create product virtual try ons',
            'edit product virtual try ons',
            'delete product virtual try ons',
        ];
        $adminRole->syncPermissions($adminPermissions);

        // Create Admin Store (Seller) role (access own store profile, NOT master stores list)
        $adminStoreRole = Role::findOrCreate('admin store', 'web');
        $adminStorePermissions = [
            'view dashboard',
            'view store profile',
            'edit store profile',
            'view products',
            'view products by store',
            'create products',
            'edit products',
            'delete products',
            'import products',
            'view categories',
            'view variant types',
            'view product reviews',
            'toggle product reviews',
            'view product virtual try ons',
            'create product virtual try ons',
        ];
        $adminStoreRole->syncPermissions($adminStorePermissions);

        // Create Customer role (API guard only so role name remains unique)
        Role::findOrCreate('customer', 'api');

        // Assign super admin role to the superadmin user or first user
        $superAdminUser = User::where('username', 'superadmin')->first() ?? User::first();
        if ($superAdminUser && ! $superAdminUser->hasRole('super admin')) {
            $superAdminUser->assignRole($superAdminRole);
        }
    }
}
