<?php

namespace App\Http\Requests\Api\UserLocation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserLocationRequest extends FormRequest
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
            'source' => ['required', 'string', Rule::in(['gps', 'manual'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_if:source,gps'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_if:source,gps'],
            'address' => ['nullable', 'string', 'max:500'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
