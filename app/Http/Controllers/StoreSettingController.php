<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Province;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StoreSettingController extends Controller
{
    public function index()
    {
        $setting = StoreSetting::first();

        if (! $setting) {
            $setting = StoreSetting::create([
                'name' => 'Toko BatikKu',
                'whatsapp' => '6281234567890',
            ]);
        }

        $provinces = Province::all();

        return Inertia::render('store-settings/Index', [
            'setting' => $setting,
            'provinces' => $provinces,
            'couriers' => Courier::orderBy('name')->get(['id', 'name', 'code', 'logo', 'is_active']),
        ]);
    }

    public function update(Request $request)
    {
        $setting = StoreSetting::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|string|max:255',
            'twitter_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'subdistrict_id' => 'nullable|integer',
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'privacy_policy' => 'nullable|string',
            'policy' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
        ]);

        if ($request->hasFile('logo_file')) {
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo_file')->store('store', 'public');
        } elseif (array_key_exists('logo', $request->all()) && $request->input('logo') === null) {
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = null;
        }

        unset($validated['logo_file']);

        $setting->update($validated);

        return redirect()->back()->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
