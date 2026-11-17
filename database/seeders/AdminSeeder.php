<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if (! $adminRole) {
            $this->call(RolePermissionSeeder::class);
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        }

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@batik.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($adminRole && ! $admin->hasRole('admin', 'web')) {
            $admin->assignRole($adminRole);
        }
    }
}
