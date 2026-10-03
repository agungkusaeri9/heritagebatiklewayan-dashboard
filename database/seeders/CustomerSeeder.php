<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $password = Hash::make('password'); // Default password for all

        for ($i = 0; $i < 20; $i++) {
            // 1. Create User
            $name = $faker->name;
            $user = User::create([
                'name' => $name,
                'email' => $faker->unique()->safeEmail,
                'username' => Str::slug($name) . '-' . rand(100, 999),
                'password' => $password,
            ]);

            // Assign role if using spatie
            // $user->assignRole('customer');

            // 2. Create Customer Profile
            $customer = Customer::create([
                'user_id' => $user->id,
                'phone' => $faker->phoneNumber,
                'is_active' => $faker->boolean(90), // 90% chance to be active
            ]);

            // 3. Create 1 or 2 Addresses
            $numAddresses = rand(1, 2);
            for ($j = 0; $j < $numAddresses; $j++) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => $j === 0 ? 'Rumah' : 'Kantor',
                    'recipient_name' => $name,
                    'phone' => $customer->phone,
                    'full_address' => $faker->streetAddress,
                    'city' => $faker->city,
                    'province' => 'Jawa Barat', // Static for simplicity or use faker
                    'postal_code' => $faker->postcode,
                    'is_primary' => $j === 0,
                ]);
            }
        }
    }
}
