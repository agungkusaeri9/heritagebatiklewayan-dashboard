<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Subdistrict;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        if ($customers->isEmpty()) {
            $this->command->warn('No customers found, skipping order seeder.');

            return;
        }

        $products = Product::with('variantValues.variantType')->get();
        if ($products->isEmpty()) {
            $this->command->warn('No products found, skipping order seeder.');

            return;
        }

        $paymentMethods = PaymentMethod::all();
        $couriers = Courier::all();

        $statuses = ['pending', 'processing', 'success', 'failed'];

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
                $price = (float) $product->price;
                $totalPrice = $price * $qty;
                $subtotal += $totalPrice;

                // Pick variant if available
                $variantParts = [];
                if ($product->variantValues && $product->variantValues->isNotEmpty()) {
                    $grouped = $product->variantValues->groupBy('variant_type_id');
                    foreach ($grouped as $typeId => $values) {
                        $chosen = $values->random();
                        $typeName = $chosen->variantType?->name ?? 'Varian';
                        $variantParts[] = "{$typeName}: {$chosen->value}";
                    }
                }

                if (empty($variantParts)) {
                    $sampleSizes = ['Ukuran: M', 'Ukuran: L', 'Ukuran: XL', 'Ukuran: XXL'];
                    $variantParts[] = $sampleSizes[array_rand($sampleSizes)];
                }

                $orderDetailsData[] = [
                    'product_id' => $product->id,
                    'variant_info' => implode(', ', $variantParts),
                    'price' => $price,
                    'quantity' => $qty,
                    'total_price' => $totalPrice,
                ];
            }

            $shippingFee = rand(10000, 50000);
            $totalAmount = $subtotal + $shippingFee;

            $status = $statuses[array_rand($statuses)];
            $subdistrict = Subdistrict::with('city')->inRandomOrder()->first();

            $shippingOptions = ['JNE - Regular', 'J&T Express', 'SiCepat REG', 'Pos Indonesia', 'TIKI ONS'];
            $shippingName = in_array($status, ['processing', 'success']) ? $shippingOptions[array_rand($shippingOptions)] : null;
            $trackingNumber = in_array($status, ['processing', 'success']) ? (strtoupper(substr($shippingName, 0, 3)).rand(100000000, 999999999)) : null;

            $order = Order::create([
                'order_number' => 'ORD-'.strtoupper(Str::random(10)),
                'customer_id' => $customer->id,
                'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'status' => $status,
                'province_id' => $subdistrict?->city?->province_id ?? 1,
                'city_id' => $subdistrict?->city_id ?? 1,
                'subdistrict_id' => $subdistrict?->subdistrict_id ?? 1,
                'address' => 'Jl. Batik No. '.rand(1, 100).', RT 01 / RW 02',
                'courier_id' => $courier ? $courier->id : null,
                'shipping_name' => $shippingName,
                'tracking_number' => $trackingNumber,
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
