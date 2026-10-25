<?php

namespace App\Http\Requests\Api\Shipping;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckShippingCostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $origin = $this->input('origin') ?? $this->input('origin_id');
        if (empty($origin)) {
            $origin = config('services.rajaongkir.origin', 6164);
        }

        $courier = $this->input('courier');
        if (empty($courier)) {
            $courier = 'jne:pos:tiki';
        }

        $this->merge([
            'origin' => $origin,
            'destination' => $this->input('destination') ?? $this->input('destination_id'),
            'courier' => $courier,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin' => ['required'],
            'destination' => ['required'],
            'weight' => ['required', 'numeric', 'min:1'],
            'courier' => ['required', 'string'],
            'price' => ['nullable'],
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
            'origin' => 'lokasi asal (origin)',
            'destination' => 'lokasi tujuan (destination)',
            'weight' => 'berat barang (gram)',
            'courier' => 'kode kurir',
            'price' => 'parameter harga',
        ];
    }
}
