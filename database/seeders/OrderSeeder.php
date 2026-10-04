<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Courier;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $customers = User::role('customer')->get();
        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Creating a dummy customer...');
            $customer = User::create([
                'name' => 'Dummy Customer',
                'email' => 'customer' . rand(1, 100) . '@example.com',
                'password' => bcrypt('password'),
            ]);
            $customer->assignRole('customer');
            $customers = collect([$customer]);
        }

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->warn('No products found, skipping order seeder.');
            return;
        }

        $paymentMethods = PaymentMethod::all();
        $couriers = Courier::all();

        $statuses = ['pending', 'processing', 'success', 'failed'];
        $paymentStatuses = ['pending', 'paid', 'failed'];

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $paymentMethod = $paymentMethods->count() > 0 ? $paymentMethods->random() : null;
            $courier = $couriers->count() > 0 ? $couriers->random() : null;

            // Select 1-3 random products, bounded by available products count
            $maxProducts = min(3, $products->count());
            $orderProducts = $products->random(rand(1, $maxProducts));
            $subtotal = 0;
            
            $orderDetailsData = [];
            foreach ($orderProducts as $product) {
                $qty = rand(1, 3);
                $price = $product->price;
                $totalPrice = $price * $qty;
                $subtotal += $totalPrice;
                
                $orderDetailsData[] = [
                    'product_id' => $product->id,
                    'price' => $price,
                    'quantity' => $qty,
                    'total_price' => $totalPrice,
                ];
            }

            $shippingFee = rand(10000, 50000);
            $totalAmount = $subtotal + $shippingFee;

            $status = $statuses[array_rand($statuses)];

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'customer_id' => $customer->id,
                'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'status' => $status,
                'province_id' => rand(1, 34),
                'city_id' => rand(1, 100),
                'subdistrict_id' => rand(1, 100),
                'address' => 'Jl. Dummy Address No. ' . rand(1, 100),
                'courier_id' => $courier ? $courier->id : null,
            ]);

            foreach ($orderDetailsData as $detail) {
                $detail['order_id'] = $order->id;
                OrderDetail::create($detail);
            }

            Transaction::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_proof' => null,
                'status' => $status == 'success' ? 'paid' : ($status == 'failed' ? 'failed' : 'pending'),
            ]);
        }
    }
}
