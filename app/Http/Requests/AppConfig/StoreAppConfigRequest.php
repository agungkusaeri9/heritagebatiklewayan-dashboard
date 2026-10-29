<?php

namespace App\Http\Requests\AppConfig;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'unique:app_configs,key'],
            'value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key' => 'Key konfigurasi',
            'value' => 'Nilai konfigurasi',
            'description' => 'Deskripsi konfigurasi',
        ];
    }
}
