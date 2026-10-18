<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'province_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'subdistrict_id' => ['nullable', 'integer'],
            'address' => ['nullable', 'string', 'max:1000'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'shipping_name' => ['nullable', 'string', 'max:255'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'from_cart' => ['nullable', 'boolean'],
            'cart_item_ids' => ['nullable', 'array'],
            'cart_item_ids.*' => ['integer'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:20'],
            'items.*.variant_info' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'address_id' => 'alamat pengiriman',
            'province_id' => 'provinsi',
            'city_id' => 'kota/kabupaten',
            'subdistrict_id' => 'kecamatan',
            'address' => 'alamat lengkap',
            'courier_id' => 'kurir pengiriman',
            'shipping_name' => 'layanan pengiriman',
            'shipping_fee' => 'ongkos kirim',
            'payment_method_id' => 'metode pembayaran',
            'items' => 'daftar item pesanan',
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'jumlah barang',
            'items.*.variant_info' => 'varian barang',
        ];
    }
}
