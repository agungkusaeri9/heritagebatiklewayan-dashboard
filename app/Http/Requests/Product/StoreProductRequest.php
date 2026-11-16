<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('category_id'), ['', 'none'], true)) {
            $this->merge(['category_id' => null]);
        }

        if (in_array($this->input('store_id'), ['', 'none'], true)) {
            $this->merge(['store_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'primary_image' => ['nullable', 'image', 'max:2048'],

            // Ecommerce fields
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'weight' => ['required', 'integer', 'min:0'],

            // Flags
            'is_virtual_try_on' => ['boolean'],
            'is_active' => ['boolean'],

            // Relations
            'variant_values' => ['nullable', 'array'],
            'variant_values.*' => ['exists:variant_values,id'],

            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'max:2048'],
        ];
    }
}
