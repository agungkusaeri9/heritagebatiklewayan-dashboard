<?php

namespace App\Http\Requests\AppVersion;

use App\Models\AppVersion;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateAppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var AppVersion|null $appVersion */
        $appVersion = $this->route('app_version');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['android', 'ios'])],
            'version' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_versions', 'version')
                    ->where('type', $this->input('type'))
                    ->ignore($appVersion?->id),
            ],
            'version_code' => ['required', 'integer', 'min:1'],
            'min_version' => ['nullable', 'string', 'max:50'],
            'file' => [
                'nullable',
                'file',
                'max:512000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['apk', 'aab', 'ipa', 'zip'], true)) {
                        $fail('File aplikasi harus berformat .apk, .aab, .ipa, atau .zip.');
                    }
                },
            ],
            'release_notes' => ['nullable', 'string'],
            'is_latest' => ['boolean'],
            'is_force_update' => ['boolean'],
            'is_maintenance' => ['boolean'],
            'maintenance_message' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'released_at' => ['nullable', 'date'],
        ];
    }
}
