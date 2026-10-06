<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class IndexNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'recipient_type' => ['nullable', 'string', 'in:all,ALL_USERS,CUSTOMER,ADMIN,SPECIFIC_USER'],
            'read_status' => ['nullable', 'string', 'in:all,all_read,has_unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:id,title,recipient_type,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
