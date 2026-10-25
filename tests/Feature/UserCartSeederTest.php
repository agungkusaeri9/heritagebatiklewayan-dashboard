<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserCartSeeder;
use Tests\TestCase;

class UserCartSeederTest extends TestCase
{
    public function test_user_cart_seeder_creates_user_and_seeds_ten_cart_products(): void
    {
        $this->seed(UserCartSeeder::class);

        $user = User::where('email', 'agus@gmail.com')->first();
        $this->assertNotNull($user);

        $cartCount = Cart::where('user_id', $user->id)->count();
        $expectedCount = min(10, Product::where('is_active', true)->count());

        $this->assertGreaterThan(0, $cartCount);
        $this->assertLessThanOrEqual(10, $cartCount);
        $this->assertEquals($expectedCount, $cartCount);
    }
}
