<?php

namespace App\Http\Controllers;

use App\Models\ProductVirtualTryOn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    /**
     * Display the landing page settings (try-on examples).
     */
    public function index(): Response
    {
        $selected = ProductVirtualTryOn::query()
            ->where('is_landing_page', true)
            ->with(['product:id,name,primary_image', 'virtualModel:id,name,gender,photo'])
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->map(fn (ProductVirtualTryOn $tryOn) => [
                'id' => $tryOn->id,
                'product_name' => $tryOn->product?->name,
                'model_name' => $tryOn->virtualModel?->name,
                'photo_url' => $tryOn->photo_url,
                'video_url' => $tryOn->video_url,
            ]);

        $selectedIds = $selected->pluck('id')->all();

        $available = ProductVirtualTryOn::query()
            ->whereNull('user_id')
            ->where('status', 'completed')
            ->where('is_landing_page', false)
            ->whereNotIn('id', $selectedIds)
            ->with(['product:id,name,primary_image', 'virtualModel:id,name,gender,photo'])
            ->latest()
            ->get()
            ->map(fn (ProductVirtualTryOn $tryOn) => [
                'id' => $tryOn->id,
                'label' => trim(($tryOn->product?->name ?? 'Produk').' — '.($tryOn->virtualModel?->name ?? ucfirst($tryOn->gender ?? 'Model'))),
                'photo_url' => $tryOn->photo_url,
            ]);

        return Inertia::render('landing-page/Index', [
            'selected' => $selected,
            'available' => $available,
            'maxExamples' => 3,
        ]);
    }

    /**
     * Update the landing page try-on examples (max 3) via is_landing_page flag.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'examples' => ['array', 'max:3'],
            'examples.*' => [
                'integer',
                \Illuminate\Validation\Rule::exists('product_virtual_try_ons', 'id')
                    ->whereNull('user_id')
                    ->where('status', 'completed'),
            ],
        ], [
            'examples.max' => 'Maksimal 3 contoh try-on untuk landing page.',
        ]);

        $ids = array_values(array_map('intval', $validated['examples'] ?? []));

        DB::transaction(function () use ($ids) {
            ProductVirtualTryOn::query()->where('is_landing_page', true)->update(['is_landing_page' => false]);

            if ($ids !== []) {
                ProductVirtualTryOn::query()->whereIn('id', $ids)->update(['is_landing_page' => true]);
            }
        });

        return redirect()->back()->with('success', 'Contoh Virtual Try-On landing page berhasil diperbarui.');
    }
}
