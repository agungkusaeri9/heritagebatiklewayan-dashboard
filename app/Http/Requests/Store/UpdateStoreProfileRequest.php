<?php

namespace App\Http\Requests\Store;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->input('email') === '' ? null : $this->input('email'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'description' => ['nullable', 'string'],
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
            'name' => 'nama toko',
            'email' => 'email toko',
            'phone' => 'nomor telepon',
            'whatsapp' => 'nomor WhatsApp',
            'logo' => 'logo toko',
            'description' => 'deskripsi toko',
        ];
    }
}
