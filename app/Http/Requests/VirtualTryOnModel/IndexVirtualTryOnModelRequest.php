<?php

namespace App\Http\Requests\VirtualTryOnModel;

use Illuminate\Foundation\Http\FormRequest;

class IndexVirtualTryOnModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view virtual try on models') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:all,male,female,unisex'],
            'is_active' => ['nullable', 'string', 'in:all,true,false,1,0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'gender' => $this->input('gender', 'all'),
            'is_active' => $this->input('is_active', 'all'),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 10);
    }
}
