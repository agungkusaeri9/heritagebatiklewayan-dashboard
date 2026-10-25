<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserCartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dapatkan atau buat pengguna dengan email agus@gmail.com
        $defaultUser = User::firstOrCreate(
            ['email' => 'agus@gmail.com'],
            [
                'name' => 'Agus Setiawan',
                'username' => 'agus-setiawan',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Pastikan pengguna memiliki role customer
        $customerRole = Role::findOrCreate('customer', 'api');
        if (! $defaultUser->hasRole('customer', 'api')) {
            $defaultUser->assignRole($customerRole);
        }

        // 3. Pastikan profil customer tersedia dan aktif
        Customer::firstOrCreate(
            ['user_id' => $defaultUser->id],
            [
                'phone' => '081234567890',
                'is_active' => true,
            ]
        );

        // 4. Ambil 10 produk aktif
        $products = Product::where('is_active', true)->take(10)->get();
        if ($products->isEmpty()) {
            $products = Product::take(10)->get();
        }

        // Variasi ukuran busana batik
        $sizes = [
            'Size: M',
            'Size: L',
            'Size: XL',
            'Size: S',
            'Size: All Size',
        ];

        // 5. Tambahkan produk ke keranjang belanja semua user (termasuk agus@gmail.com)
        $users = User::all();
        if ($users->isEmpty()) {
            $users = collect([$defaultUser]);
        }

        foreach ($users as $user) {
            foreach ($products as $index => $product) {
                $variant = $sizes[$index % count($sizes)];
                $quantity = ($index % 3) + 1; // kuantitas variasi antara 1, 2, atau 3

                Cart::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'variant_info' => $variant,
                    ]
                );
            }
        }
    }
}
