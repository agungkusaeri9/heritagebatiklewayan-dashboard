<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $param = $this->route('notification_setting') ?? $this->route('setting');
        $settingId = is_object($param) ? $param->id : ($param ?? $this->input('id'));

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('notification_settings', 'code')->ignore($settingId),
            ],
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
