<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailVerificationOtpRequest extends FormRequest
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
            'email' => [$this->user() ? 'nullable' : 'required', 'string', 'email'],
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }

    /**
     * Get the email to verify.
     */
    public function getEmail(): string
    {
        return (string) ($this->user()?->email ?? $this->input('email'));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'alamat email',
            'otp' => 'kode OTP',
        ];
    }
}
