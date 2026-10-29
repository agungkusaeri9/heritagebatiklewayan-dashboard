<?php

namespace App\Http\Requests\ProductVirtualTryOn;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductVirtualTryOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view product virtual try ons') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable'],
            'user_id' => ['nullable'],
            'model_id' => ['nullable'],
            'without_user' => ['nullable'],
            'user_type' => ['nullable', 'string', 'in:all,without_user,with_user,default,user'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'product_id' => $this->input('product_id', 'all'),
            'user_id' => $this->input('user_id', 'all'),
            'model_id' => $this->input('model_id', 'all'),
            'without_user' => $this->input('without_user'),
            'user_type' => $this->input('user_type', 'all'),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 10);
    }
}
