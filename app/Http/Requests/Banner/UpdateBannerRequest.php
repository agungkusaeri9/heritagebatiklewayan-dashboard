<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $this->is_active,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'target_type' => ['required', 'string', 'in:none,product,category,url'],
            'product_id' => ['nullable', 'required_if:target_type,product', 'exists:products,id'],
            'category_id' => ['nullable', 'required_if:target_type,category', 'exists:categories,id'],
            'target_url' => ['nullable', 'required_if:target_type,url', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul banner wajib diisi.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar yang diperbolehkan: JPEG, JPG, PNG, WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'product_id.required_if' => 'Pilih produk tujuan untuk banner ini.',
            'category_id.required_if' => 'Pilih kategori tujuan untuk banner ini.',
            'target_url.required_if' => 'URL tujuan wajib diisi jika target adalah URL.',
            'target_url.url' => 'Format URL tidak valid. Contoh: https://example.com/promo',
            'end_date.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal mulai tayang.',
        ];
    }
}
