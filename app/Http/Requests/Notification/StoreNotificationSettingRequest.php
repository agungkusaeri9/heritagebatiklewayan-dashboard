<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'unique:notification_settings,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recipient_type' => ['required', 'string', 'in:ALL_USERS,CUSTOMER,ADMIN,SPECIFIC_USER'],
            'push_enabled' => ['boolean'],
            'email_enabled' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
