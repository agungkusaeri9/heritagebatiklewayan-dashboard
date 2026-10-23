<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Display the privacy and policy settings page.
     */
    public function index(): Response
    {
        $setting = StoreSetting::first();

        if (! $setting) {
            $setting = StoreSetting::create([
                'name' => 'Toko BatikKu',
                'whatsapp' => '6281234567890',
            ]);
        }

        return Inertia::render('privacy-policy/Index', [
            'setting' => [
                'id' => $setting->id,
                'privacy_policy' => $setting->privacy_policy ?? '',
                'policy' => $setting->policy ?? '',
                'cancellation_policy' => $setting->cancellation_policy ?? '',
                'updated_at' => $setting->updated_at?->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Update the privacy and policy contents.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'privacy_policy' => ['nullable', 'string'],
            'policy' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
        ]);

        $setting = StoreSetting::first();

        if (! $setting) {
            $setting = StoreSetting::create([
                'name' => 'Toko BatikKu',
                'whatsapp' => '6281234567890',
            ]);
        }

        $setting->update($validated);

        return redirect()->back()->with('success', 'Kebijakan dan Privasi berhasil diperbarui.');
    }
}
