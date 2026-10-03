<?php

namespace App\Http\Controllers;

use App\Http\Requests\VariantType\IndexVariantTypeRequest;
use App\Http\Requests\VariantType\StoreVariantTypeRequest;
use App\Http\Requests\VariantType\SyncVariantValuesRequest;
use App\Http\Requests\VariantType\UpdateVariantTypeRequest;
use App\Models\VariantType;
use App\Services\VariantTypeService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VariantTypeController extends Controller
{
    public function __construct(
        private VariantTypeService $variantTypeService
    ) {}

    public function index(IndexVariantTypeRequest $request)
    {
        $variantTypes = $this->variantTypeService->getVariantTypes($request->filters(), $request->perPage());

        return Inertia::render('variant-types/Index', [
            'variantTypes' => $variantTypes->through(fn ($vt) => [
                'id' => $vt->id,
                'name' => $vt->name,
                'slug' => $vt->slug,
                'description' => $vt->description,
                'is_active' => (bool) $vt->is_active,
                'sort_order' => $vt->sort_order,
                'values_count' => $vt->values_count,
                'created_at' => $vt->created_at,
            ]),
            'filters' => $request->filters(),
        ]);
    }

    public function create()
    {
        return Inertia::render('variant-types/Create');
    }

    public function store(StoreVariantTypeRequest $request)
    {
        $validated = $request->validated();

        try {
            $values = $validated['values'] ?? [];
            unset($validated['values']);

            $this->variantTypeService->createVariantType($validated, $values);

            return redirect()->route('variant-types.index')->with('success', 'Varian produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan varian: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menambahkan varian: '.$e->getMessage())->withInput();
        }
    }

    public function edit(VariantType $variantType)
    {
        return Inertia::render('variant-types/Edit', [
            'variantType' => [
                'id' => $variantType->id,
                'name' => $variantType->name,
                'slug' => $variantType->slug,
                'description' => $variantType->description,
                'is_active' => (bool) $variantType->is_active,
                'sort_order' => $variantType->sort_order,
            ],
        ]);
    }

    public function update(UpdateVariantTypeRequest $request, VariantType $variantType)
    {
        $validated = $request->validated();

        try {
            $this->variantTypeService->updateVariantType($variantType, $validated);

            return redirect()->route('variant-types.index')->with('success', 'Varian produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui varian: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui varian: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(VariantType $variantType)
    {
        try {
            $this->variantTypeService->deleteVariantType($variantType);

            return back()->with('success', 'Varian produk berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus varian: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus varian: '.$e->getMessage());
        }
    }

    /**
     * Show the variant values management page for a specific variant type.
     */
    public function values(VariantType $variantType)
    {
        $variantType->load('values');

        return Inertia::render('variant-types/Values', [
            'variantType' => [
                'id' => $variantType->id,
                'name' => $variantType->name,
                'slug' => $variantType->slug,
                'is_active' => (bool) $variantType->is_active,
                'values' => $variantType->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'sort_order' => $v->sort_order,
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Sync variant values for a specific variant type.
     */
    public function syncValues(SyncVariantValuesRequest $request, VariantType $variantType)
    {
        $validated = $request->validated();

        try {
            $this->variantTypeService->syncValues($variantType, $validated['values']);

            return back()->with('success', 'Nilai varian berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan nilai varian: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menyimpan nilai varian: '.$e->getMessage());
        }
    }
}
