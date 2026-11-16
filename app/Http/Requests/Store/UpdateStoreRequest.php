<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->input('email') === '' ? null : $this->input('email'),
            'seller_email' => $this->input('seller_email') === '' ? null : $this->input('seller_email'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $store = $this->route('store');
        $currentUserId = $store instanceof Store ? $store->user_id : null;
        $hasSellerPayload = $currentUserId !== null
            && ($this->has('seller_name') || $this->has('seller_username') || $this->has('seller_email') || $this->filled('seller_password'));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'description' => ['nullable', 'string'],
        ];

        if ($hasSellerPayload) {
            $rules['seller_name'] = ['required', 'string', 'max:255'];
            $rules['seller_username'] = ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($currentUserId)];
            $rules['seller_email'] = ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($currentUserId)];
            $rules['seller_password'] = ['nullable', 'string', 'min:8'];
        }

        return $rules;
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
            'seller_name' => 'nama lengkap seller',
            'seller_username' => 'username seller',
            'seller_email' => 'email seller',
            'seller_password' => 'kata sandi seller',
        ];
    }
}
