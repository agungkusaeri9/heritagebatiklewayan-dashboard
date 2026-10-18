<?php

namespace App\Http\Requests\Api\Review;

use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('product_id') && ! $this->has('product_id')) {
            $this->merge([
                'product_id' => $this->route('product_id'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $customer = $user?->customer;

            if (! $customer || ! $customer->is_active) {
                $validator->errors()->add('order_id', 'Akun Anda tidak memiliki profil customer aktif.');

                return;
            }

            $orderId = (int) $this->input('order_id');
            $productId = (int) $this->input('product_id');

            $order = Order::with(['transaction', 'details'])->find($orderId);

            if (! $order || $order->customer_id !== $customer->id) {
                $validator->errors()->add('order_id', 'Pesanan tidak ditemukan atau bukan milik akun Anda.');

                return;
            }

            // 1. Order status must be finished / completed ('success')
            if ($order->status !== 'success') {
                $validator->errors()->add('order_id', 'Ulasan hanya dapat diberikan untuk pesanan yang telah selesai (status: success).');

                return;
            }

            // 2. Payment status must be paid
            if (! $order->transaction || $order->transaction->status !== 'paid') {
                $validator->errors()->add('order_id', 'Ulasan hanya dapat diberikan jika pembayaran pesanan sudah lunas (status: paid).');

                return;
            }

            // 3. Product must be in order details
            $hasProduct = $order->details->contains('product_id', $productId);
            if (! $hasProduct) {
                $validator->errors()->add('product_id', 'Produk ini tidak terdapat dalam rincian pesanan yang dipilih.');

                return;
            }

            // 4. Check if product in this order has already been reviewed
            $alreadyReviewed = ProductReview::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->exists();

            if ($alreadyReviewed) {
                $validator->errors()->add('product_id', 'Anda sudah memberikan ulasan untuk produk ini pada pesanan tersebut.');
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'order_id' => 'pesanan',
            'product_id' => 'produk',
            'rating' => 'penilaian (bintang)',
            'comment' => 'komentar ulasan',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Rating bintang 1-5 wajib dipilih.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.max' => 'Komentar ulasan maksimal 1000 karakter.',
        ];
    }
}
