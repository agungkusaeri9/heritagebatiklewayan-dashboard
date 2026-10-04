<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Http\Requests\Courier\IndexCourierRequest;
use App\Http\Requests\Courier\StoreCourierRequest;
use App\Http\Requests\Courier\UpdateCourierRequest;
use App\Services\CourierService;
use Inertia\Inertia;

class CourierController extends Controller
{
    public function __construct(
        protected CourierService $courierService
    ) {}

    public function index(IndexCourierRequest $request)
    {
        $couriers = $this->courierService->getPaginatedCouriers(
            $request->input('per_page', 10),
            $request->input('search'),
            $request->input('status'),
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return Inertia::render('couriers/Index', [
            'couriers' => $couriers,
            'filters' => $request->only(['search', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function store(StoreCourierRequest $request)
    {
        try {
            $this->courierService->createCourier($request->validated());
            return redirect()->route('couriers.index')->with('success', 'Kurir berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan kurir: ' . $e->getMessage())->withInput();
        }
    }

    public function update(UpdateCourierRequest $request, Courier $courier)
    {
        try {
            $this->courierService->updateCourier($courier->id, $request->validated());
            return redirect()->route('couriers.index')->with('success', 'Kurir berhasil diubah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah kurir: ' . $e->getMessage())->withInput();
        }
    }

    public function toggle(Courier $courier)
    {
        try {
            $courier->update(['is_active' => !$courier->is_active]);
            return back()->with('success', 'Status kurir berhasil diubah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah status kurir: ' . $e->getMessage());
        }
    }

    public function destroy(Courier $courier)
    {
        try {
            $this->courierService->deleteCourier($courier->id);
            return redirect()->route('couriers.index')->with('success', 'Kurir berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus kurir: ' . $e->getMessage());
        }
    }
}
