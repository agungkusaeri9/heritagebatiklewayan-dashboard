<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Services\PaymentMethodService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethodRepositoryInterface $paymentMethodRepository,
        protected PaymentMethodService $paymentMethodService
    ) {}

    public function index(Request $request)
    {
        $paymentMethods = $this->paymentMethodRepository->getPaginatedPaymentMethods(
            $request->input('per_page', 10),
            $request->input('search'),
            $request->input('status'),
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return Inertia::render('payment-methods/Index', [
            'paymentMethods' => $paymentMethods,
            'filters' => $request->only(['search', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        // Fix boolean from frontend
        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $this->paymentMethodService->createPaymentMethod($validated);
            return back()->with('success', 'Metode pembayaran berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan metode pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $this->paymentMethodService->updatePaymentMethod($id, $validated);
            return back()->with('success', 'Metode pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui metode pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->paymentMethodService->deletePaymentMethod($id);
            return back()->with('success', 'Metode pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus metode pembayaran: ' . $e->getMessage());
        }
    }
}
