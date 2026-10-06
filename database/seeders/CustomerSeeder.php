<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Subdistrict;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $password = Hash::make('password');

        $sampleSubdistricts = Subdistrict::with('city')->inRandomOrder()->limit(30)->get();

        for ($i = 0; $i < 20; $i++) {
            $name = $faker->name;
            $user = User::create([
                'name' => $name,
                'email' => $faker->unique()->safeEmail,
                'username' => Str::slug($name).'-'.rand(100, 999),
                'password' => $password,
            ]);

            $user->assignRole('customer');

            $customer = Customer::create([
                'user_id' => $user->id,
                'phone' => $faker->phoneNumber,
                'is_active' => $faker->boolean(90),
            ]);

            $numAddresses = rand(1, 2);
            for ($j = 0; $j < $numAddresses; $j++) {
                $subdistrict = $sampleSubdistricts->isNotEmpty() ? $sampleSubdistricts->random() : null;

                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => $j === 0 ? 'Rumah' : 'Kantor',
                    'recipient_name' => $name,
                    'phone' => $customer->phone,
                    'full_address' => $faker->streetAddress,
                    'province_id' => $subdistrict?->city?->province_id,
                    'city_id' => $subdistrict?->city_id,
                    'subdistrict_id' => $subdistrict?->subdistrict_id,
                    'postal_code' => $faker->postcode,
                    'is_primary' => $j === 0,
                ]);
            }
        }
    }
}
