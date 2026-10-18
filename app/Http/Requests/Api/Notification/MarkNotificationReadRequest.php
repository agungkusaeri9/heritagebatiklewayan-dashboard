<?php

namespace App\Http\Requests\Api\Notification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all' => ['nullable', 'boolean'],
            'notification_ids' => ['nullable', 'array'],
            'notification_ids.*' => ['integer', 'exists:notifications,id'],
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
            'all' => 'tandai semua',
            'notification_ids' => 'daftar ID notifikasi',
        ];
    }
}
