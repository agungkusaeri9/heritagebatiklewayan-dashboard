<?php

namespace App\Http\Requests\ProductVirtualTryOn;

use App\Models\ProductVirtualTryOn;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVirtualTryOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create product virtual try ons') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'virtual_try_on_model_id' => ['required', 'integer', 'exists:virtual_try_on_models,id'],
            'gender' => ['required', 'string', 'in:male,female'],
            'generate_video' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productId = $this->input('product_id');
            $gender = $this->input('gender');

            if ($productId && $gender) {
                $alreadyExists = ProductVirtualTryOn::where('product_id', $productId)
                    ->whereNull('user_id')
                    ->where('gender', $gender)
                    ->where('status', '!=', 'failed')
                    ->exists();

                if ($alreadyExists) {
                    $genderLabel = $gender === 'male' ? 'Laki-laki (Pria)' : 'Perempuan (Wanita)';
                    $validator->errors()->add(
                        'gender',
                        "Produk ini sudah memiliki model default untuk gender {$genderLabel}. Maksimal hanya boleh 1 model pria dan 1 model wanita per produk."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid.',
            'virtual_try_on_model_id.required' => 'Model virtual wajib dipilih.',
            'virtual_try_on_model_id.exists' => 'Model virtual yang dipilih tidak valid.',
            'gender.required' => 'Gender model default wajib dipilih.',
            'gender.in' => 'Gender harus berupa male atau female.',
        ];
    }
}
