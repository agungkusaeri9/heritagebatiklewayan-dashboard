<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVirtualTryOn;
use App\Models\User;
use App\Models\VirtualTryOnModel;
use Illuminate\Database\Seeder;

class VirtualTryOnSeeder extends Seeder
{
    public function run(): void
    {
        $modelsData = [
            [
                'name' => 'Model Pria Formal',
                'gender' => 'male',
                'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Model Wanita Kasual',
                'gender' => 'female',
                'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Model Unisex Modern',
                'gender' => 'unisex',
                'photo' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=800&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Model Wanita Kebaya & Batik',
                'gender' => 'female',
                'photo' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=800&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Model Pria Kemeja Batik',
                'gender' => 'male',
                'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=800&auto=format&fit=crop&q=80',
                'is_active' => true,
            ],
        ];

        $createdModels = [];
        foreach ($modelsData as $data) {
            $createdModels[] = VirtualTryOnModel::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        $products = Product::take(5)->get();
        $users = User::take(5)->get();

        if ($products->isNotEmpty()) {
            // 1. Model default product (without user)
            ProductVirtualTryOn::firstOrCreate(
                [
                    'product_id' => $products[0]->id,
                    'virtual_try_on_model_id' => $createdModels[0]->id,
                    'user_id' => null,
                ],
                [
                    'gender' => 'male',
                    'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&auto=format&fit=crop&q=80',
                    'video' => null,
                ]
            );

            if (isset($products[1]) && isset($createdModels[1])) {
                ProductVirtualTryOn::firstOrCreate(
                    [
                        'product_id' => $products[1]->id,
                        'virtual_try_on_model_id' => $createdModels[1]->id,
                        'user_id' => null,
                    ],
                    [
                        'gender' => 'female',
                        'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&auto=format&fit=crop&q=80',
                        'video' => null,
                    ]
                );
            }

            // 2. User try-on with model preset
            if ($users->isNotEmpty() && isset($createdModels[0])) {
                ProductVirtualTryOn::firstOrCreate(
                    [
                        'product_id' => $products[0]->id,
                        'virtual_try_on_model_id' => $createdModels[0]->id,
                        'user_id' => $users[0]->id,
                    ],
                    [
                        'gender' => 'male',
                        'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&auto=format&fit=crop&q=80',
                        'video' => null,
                    ]
                );
            }

            // 3. User try-on with custom user photo (no model preset)
            if (isset($users[1]) && isset($products[1])) {
                ProductVirtualTryOn::firstOrCreate(
                    [
                        'product_id' => $products[1]->id,
                        'virtual_try_on_model_id' => null,
                        'user_id' => $users[1]->id,
                    ],
                    [
                        'gender' => 'female',
                        'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&auto=format&fit=crop&q=80',
                        'video' => null,
                    ]
                );
            }
        }
    }
}
