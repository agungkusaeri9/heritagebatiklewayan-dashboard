<?php

namespace App\Http\Requests\AppConfig;

use App\Models\AppConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppConfigRequest extends FormRequest
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
        $appConfig = $this->route('app_config');
        $id = $appConfig instanceof AppConfig ? $appConfig->id : $appConfig;

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('app_configs', 'key')->ignore($id),
            ],
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
