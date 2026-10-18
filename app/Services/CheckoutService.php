<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Courier;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckoutService
{
    /**
     * Process checkout and create a new order.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws AccessDeniedHttpException
     */
    public function checkout(User $user, array $data): Order
    {
        $customer = $user->customer;

        if (! $customer || ! $customer->is_active) {
            throw new AccessDeniedHttpException('Akun ini tidak memiliki profil customer yang aktif.');
        }

        [$itemsToProcess, $cartIdsToDelete] = $this->resolveItems($user, $data);
        [$provinceId, $cityId, $subdistrictId, $addressText] = $this->resolveAddress($customer, $data);
        [$courierId, $shippingName, $shippingFee] = $this->resolveShipping($data);
        $paymentMethodId = $this->resolvePaymentMethod($data);

        return DB::transaction(function () use (
            $customer,
            $itemsToProcess,
            $cartIdsToDelete,
            $provinceId,
            $cityId,
            $subdistrictId,
            $addressText,
            $courierId,
            $shippingName,
            $shippingFee,
            $paymentMethodId
        ) {
            $subtotal = 0;
            $preparedDetails = [];

            foreach ($itemsToProcess as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (! $product || ! $product->is_active) {
                    $name = $product ? $product->name : 'ID '.$item['product_id'];
                    throw ValidationException::withMessages([
                        'items' => "Produk '{$name}' tidak tersedia atau sedang dinonaktifkan.",
                    ]);
                }

                $qty = (int) $item['quantity'];

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk '{$product->name}' tidak mencukupi (tersisa: {$product->stock}).",
                    ]);
                }

                $price = (float) $product->price;
                $totalPrice = $price * $qty;
                $subtotal += $totalPrice;

                // Decrement product stock
                $product->decrement('stock', $qty);

                $preparedDetails[] = [
                    'product_id' => $product->id,
                    'variant_info' => $item['variant_info'] ?? null,
                    'price' => $price,
                    'quantity' => $qty,
                    'total_price' => $totalPrice,
                ];
            }

            $totalAmount = $subtotal + $shippingFee;
            $orderNumber = $this->generateOrderNumber();

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'payment_method_id' => $paymentMethodId,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'subdistrict_id' => $subdistrictId,
                'address' => $addressText,
                'courier_id' => $courierId,
                'shipping_name' => $shippingName,
                'status' => 'pending',
            ]);

            foreach ($preparedDetails as $detail) {
                $order->details()->create($detail);
            }

            Transaction::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            if (! empty($cartIdsToDelete)) {
                Cart::whereIn('id', $cartIdsToDelete)->delete();
            }

            return $order->load([
                'customer',
                'paymentMethod',
                'courier',
                'province',
                'city',
                'subdistrict',
                'transaction',
                'details.product.images',
            ]);
        });
    }

    /**
     * Preview calculation of checkout without modifying database.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     * @throws AccessDeniedHttpException
     */
    public function preview(User $user, array $data): array
    {
        $customer = $user->customer;

        if (! $customer || ! $customer->is_active) {
            throw new AccessDeniedHttpException('Akun ini tidak memiliki profil customer yang aktif.');
        }

        [$itemsToProcess] = $this->resolveItems($user, $data);
        [$provinceId, $cityId, $subdistrictId, $addressText] = $this->resolveAddress($customer, $data);
        [$courierId, $shippingName, $shippingFee] = $this->resolveShipping($data);
        $paymentMethodId = $this->resolvePaymentMethod($data);

        $subtotal = 0;
        $totalWeight = 0;
        $previewItems = [];

        foreach ($itemsToProcess as $item) {
            $product = Product::find($item['product_id']);

            if (! $product || ! $product->is_active) {
                $name = $product ? $product->name : 'ID '.$item['product_id'];
                throw ValidationException::withMessages([
                    'items' => "Produk '{$name}' tidak tersedia atau sedang dinonaktifkan.",
                ]);
            }

            $qty = (int) $item['quantity'];
            $price = (float) $product->price;
            $totalPrice = $price * $qty;
            $subtotal += $totalPrice;
            $totalWeight += ((int) ($product->weight ?? 0)) * $qty;

            $previewItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'primary_image' => $product->primary_image_url,
                'variant_info' => $item['variant_info'] ?? null,
                'price' => $price,
                'quantity' => $qty,
                'total_price' => $totalPrice,
                'weight' => (int) ($product->weight ?? 0) * $qty,
                'stock_available' => $product->stock,
                'is_stock_sufficient' => $product->stock >= $qty,
            ];
        }

        $totalAmount = $subtotal + $shippingFee;

        return [
            'summary' => [
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'total_items' => count($previewItems),
                'total_weight' => $totalWeight,
            ],
            'shipping' => [
                'courier_id' => $courierId,
                'shipping_name' => $shippingName,
                'shipping_fee' => $shippingFee,
                'address' => [
                    'province_id' => $provinceId,
                    'city_id' => $cityId,
                    'subdistrict_id' => $subdistrictId,
                    'address' => $addressText,
                ],
            ],
            'payment' => [
                'payment_method_id' => $paymentMethodId,
            ],
            'items' => $previewItems,
        ];
    }

    /**
     * Resolve items from direct input or user's cart.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<int, array{product_id: int, quantity: int, variant_info: ?string}>, 1: array<int, int>}
     *
     * @throws ValidationException
     */
    protected function resolveItems(User $user, array $data): array
    {
        if (! empty($data['items']) && is_array($data['items'])) {
            return [$data['items'], []];
        }

        $cartQuery = Cart::where('user_id', $user->id)->with('product');

        if (! empty($data['cart_item_ids']) && is_array($data['cart_item_ids'])) {
            $cartQuery->whereIn('id', $data['cart_item_ids']);
        }

        $cartItems = $cartQuery->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Keranjang belanja Anda kosong atau item yang dipilih tidak ditemukan.',
            ]);
        }

        $items = $cartItems->map(fn ($cart) => [
            'product_id' => (int) $cart->product_id,
            'quantity' => (int) $cart->quantity,
            'variant_info' => $cart->variant_info,
        ])->all();

        $cartIdsToDelete = $cartItems->pluck('id')->all();

        return [$items, $cartIdsToDelete];
    }

    /**
     * Resolve destination shipping address.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: ?int, 1: ?int, 2: ?int, 3: string}
     *
     * @throws ValidationException
     */
    protected function resolveAddress(object $customer, array $data): array
    {
        if (! empty($data['address_id'])) {
            $address = CustomerAddress::where('id', $data['address_id'])
                ->where('customer_id', $customer->id)
                ->first();

            if (! $address) {
                throw ValidationException::withMessages([
                    'address_id' => 'Alamat pengiriman yang dipilih tidak ditemukan atau bukan milik Anda.',
                ]);
            }

            $addressText = $address->recipient_name
                ? "{$address->recipient_name} ({$address->phone}) - {$address->full_address}"
                : $address->full_address;

            if ($address->postal_code) {
                $addressText .= ", {$address->postal_code}";
            }

            return [
                $address->province_id,
                $address->city_id,
                $address->subdistrict_id,
                $addressText,
            ];
        }

        if (! empty($data['address'])) {
            $provinceId = isset($data['province_id']) ? (int) $data['province_id'] : null;
            $cityId = isset($data['city_id']) ? (int) $data['city_id'] : null;
            $subdistrictId = isset($data['subdistrict_id']) ? (int) $data['subdistrict_id'] : null;

            if (! $provinceId || ! $cityId || ! $subdistrictId) {
                throw ValidationException::withMessages([
                    'address' => 'ID Provinsi, Kota, dan Kecamatan wajib disertakan saat mengisi alamat manual.',
                ]);
            }

            return [$provinceId, $cityId, $subdistrictId, (string) $data['address']];
        }

        // Fallback to customer's primary address
        $primaryAddress = CustomerAddress::where('customer_id', $customer->id)
            ->where('is_primary', true)
            ->first();

        if ($primaryAddress) {
            $addressText = $primaryAddress->recipient_name
                ? "{$primaryAddress->recipient_name} ({$primaryAddress->phone}) - {$primaryAddress->full_address}"
                : $primaryAddress->full_address;

            if ($primaryAddress->postal_code) {
                $addressText .= ", {$primaryAddress->postal_code}";
            }

            return [
                $primaryAddress->province_id,
                $primaryAddress->city_id,
                $primaryAddress->subdistrict_id,
                $addressText,
            ];
        }

        throw ValidationException::withMessages([
            'address' => 'Silakan pilih alamat pengiriman atau masukkan alamat pengiriman Anda.',
        ]);
    }

    /**
     * Resolve courier and shipping details.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: ?int, 1: string, 2: float}
     *
     * @throws ValidationException
     */
    protected function resolveShipping(array $data): array
    {
        $courierId = ! empty($data['courier_id']) ? (int) $data['courier_id'] : null;
        $shippingName = $data['shipping_name'] ?? null;
        $shippingFee = isset($data['shipping_fee']) ? (float) $data['shipping_fee'] : 0.0;

        if ($courierId) {
            $courier = Courier::find($courierId);
            if (! $courier || ! $courier->is_active) {
                throw ValidationException::withMessages([
                    'courier_id' => 'Kurir pengiriman yang dipilih tidak tersedia atau tidak aktif.',
                ]);
            }

            if (! $shippingName) {
                $shippingName = $courier->name;
            }
        } else {
            $shippingName = $shippingName ?: 'Pengiriman Standar';
        }

        return [$courierId, $shippingName, $shippingFee];
    }

    /**
     * Resolve payment method.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function resolvePaymentMethod(array $data): ?int
    {
        $paymentMethodId = ! empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null;

        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::find($paymentMethodId);
            if (! $paymentMethod || ! $paymentMethod->is_active) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'Metode pembayaran yang dipilih tidak tersedia atau tidak aktif.',
                ]);
            }
        }

        return $paymentMethodId;
    }

    /**
     * Generate unique order number.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
