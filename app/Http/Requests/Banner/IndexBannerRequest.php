<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class IndexBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:all,active,inactive,1,0'],
            'target_type' => ['nullable', 'string', 'in:all,none,product,category,url'],
            'sort' => ['nullable', 'string', 'in:title,sort_order,is_active,start_date,end_date,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'status' => $this->input('status', 'all'),
            'target_type' => $this->input('target_type', 'all'),
            'sort' => $this->input('sort', 'sort_order'),
            'direction' => $this->input('direction', 'asc'),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 10);
    }
}
