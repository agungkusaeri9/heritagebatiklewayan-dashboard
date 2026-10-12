<?php

namespace App\Http\Requests\Api\CustomerAddress;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerAddressRequest extends FormRequest
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
     * Location fields (province, city, subdistrict) must be sent together so their hierarchy can be validated.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:50'],
            'recipient_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'full_address' => ['sometimes', 'required', 'string', 'max:1000'],
            'province_id' => [
                'required_with:city_id,subdistrict_id',
                'integer',
                Rule::exists('provinces', 'province_id'),
            ],
            'city_id' => [
                'required_with:province_id,subdistrict_id',
                'integer',
                Rule::exists('cities', 'city_id')->where('province_id', $this->integer('province_id')),
            ],
            'subdistrict_id' => [
                'required_with:province_id,city_id',
                'integer',
                Rule::exists('subdistricts', 'subdistrict_id')->where('city_id', $this->integer('city_id')),
            ],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'is_primary' => ['sometimes', 'boolean'],
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
            'label' => 'label alamat',
            'recipient_name' => 'nama penerima',
            'phone' => 'nomor telepon',
            'full_address' => 'alamat lengkap',
            'province_id' => 'provinsi',
            'city_id' => 'kota/kabupaten',
            'subdistrict_id' => 'kecamatan',
            'postal_code' => 'kode pos',
            'is_primary' => 'alamat utama',
        ];
    }
}
