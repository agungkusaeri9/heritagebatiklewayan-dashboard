<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_setting_id' => ['nullable', 'exists:notification_settings,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'recipient_type' => ['required', 'string', 'in:ALL_USERS,CUSTOMER,ADMIN,SPECIFIC_USER'],
            'user_ids' => ['nullable', 'array', 'required_if:recipient_type,SPECIFIC_USER'],
            'user_ids.*' => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul notifikasi wajib diisi.',
            'message.required' => 'Isi pesan notifikasi wajib diisi.',
            'recipient_type.required' => 'Target penerima wajib dipilih.',
            'user_ids.required_if' => 'Pilih minimal satu pengguna untuk target pengguna tertentu.',
        ];
    }
}
