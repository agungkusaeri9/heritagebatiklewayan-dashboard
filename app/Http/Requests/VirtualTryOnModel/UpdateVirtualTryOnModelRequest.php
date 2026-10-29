<?php

namespace App\Http\Requests\VirtualTryOnModel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVirtualTryOnModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit virtual try on models') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:male,female,unisex'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama model wajib diisi.',
            'gender.required' => 'Gender model wajib dipilih.',
            'gender.in' => 'Gender harus berupa male, female, atau unisex.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
            'photo.max' => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
