<?php

namespace App\Http\Requests\VirtualTryOnModel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVirtualTryOnModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create virtual try on models') ?? false;
    }

    public function rules(): array
    {
        $isManual = $this->input('creation_mode', 'manual') === 'manual';
        $hasGeneratedPath = ! empty($this->input('generated_photo_path'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:male,female,unisex'],
            'creation_mode' => ['nullable', 'string', 'in:manual,ai'],
            'photo' => [
                Rule::requiredIf($isManual && ! $hasGeneratedPath),
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
            'prompt' => [
                Rule::requiredIf(! $isManual && ! $hasGeneratedPath),
                'nullable',
                'string',
                'max:1000',
            ],
            'generated_photo_path' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama model wajib diisi.',
            'gender.required' => 'Gender model wajib dipilih.',
            'gender.in' => 'Gender harus berupa male, female, atau unisex.',
            'photo.required' => 'Foto model wajib diunggah untuk mode manual.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
            'photo.max' => 'Ukuran gambar maksimal 5MB.',
            'prompt.required' => 'Prompt deskripsi model wajib diisi untuk mode generasi AI.',
        ];
    }
}
