<?php

namespace App\Http\Requests\Review;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'product_id' => ['required', 'exists:products,id'],
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
            $customer = Customer::where('user_id', $user->id)->first();

            if (! $customer && ! $user->hasRole('super admin')) {
                $validator->errors()->add('order_id', 'Akun Anda tidak terdaftar sebagai pelanggan.');

                return;
            }

            $orderId = $this->input('order_id');
            $productId = $this->input('product_id');

            // Verify order belongs to customer and is completed
            $orderQuery = Order::where('id', $orderId)
                ->where('status', 'success')
                ->whereHas('details', fn ($q) => $q->where('product_id', $productId));

            if ($customer && ! $user->hasRole('super admin')) {
                $orderQuery->where('customer_id', $customer->id);
            }

            $order = $orderQuery->first();

            if (! $order) {
                $validator->errors()->add('order_id', 'Ulasan hanya dapat diberikan untuk produk dari pesanan yang telah selesai/sukses.');

                return;
            }

            // Check if already reviewed
            $alreadyReviewed = ProductReview::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->exists();

            if ($alreadyReviewed) {
                $validator->errors()->add('product_id', 'Anda sudah memberikan ulasan untuk produk ini pada pesanan tersebut.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating bintang 1-5 wajib dipilih.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.max' => 'Komentar maksimal 1000 karakter.',
        ];
    }
}
