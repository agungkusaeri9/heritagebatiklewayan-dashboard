<?php

namespace App\Http\Requests\Api\VirtualTryOn;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVirtualTryOnApiRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'virtual_try_on_model_id' => ['nullable', 'required_without:model_photo', 'integer', 'exists:virtual_try_on_models,id'],
            'model_photo' => ['nullable', 'required_without:virtual_try_on_model_id', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'gender' => ['nullable', 'string', 'in:male,female,unisex'],
            'generate_video' => ['nullable', 'boolean'],
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
            'product_id' => 'produk',
            'virtual_try_on_model_id' => 'model avatar',
            'model_photo' => 'foto galeri model',
            'gender' => 'jenis kelamin model',
            'generate_video' => 'generasi video',
        ];
    }
}
