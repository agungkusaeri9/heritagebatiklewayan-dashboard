<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexProductApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,price,name,stock'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'status' => ['nullable', 'string', 'in:active,inactive,all'],
        ];
    }
}
