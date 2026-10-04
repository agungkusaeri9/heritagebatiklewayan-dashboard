<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    public function index()
    {
        $setting = StoreSetting::first();
        
        if (!$setting) {
            $setting = StoreSetting::create([
                'name' => 'Toko BatikKu',
                'whatsapp' => '6281234567890',
            ]);
        }

        $couriers = \App\Models\Courier::all();
        $provinces = \App\Models\Province::all();

        return Inertia::render('store-settings/Index', [
            'setting' => $setting,
            'couriers' => $couriers,
            'provinces' => $provinces,
        ]);
    }

    public function update(Request $request)
    {
        $setting = StoreSetting::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'subdistrict_id' => 'nullable|integer',
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('logo_file')) {
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo_file')->store('store', 'public');
        } else if (array_key_exists('logo', $request->all()) && $request->input('logo') === null) {
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
