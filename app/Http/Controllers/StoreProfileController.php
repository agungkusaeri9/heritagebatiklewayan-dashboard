<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\UpdateStoreProfileRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StoreProfileController extends Controller
{
    public function __construct(
        private StoreService $storeService
    ) {}

    private function authorizeStoreProfileAccess(?User $user, string $permission): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('super admin')) {
            return;
        }

        if ($user->roles()->exists() && ! $user->can($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses Profile Toko.');
        }
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeStoreProfileAccess($user, 'view store profile');

        $store = Store::query()
            ->with(['user.roles'])
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('store-profile/Index', [
            'store' => $store ? [
                'id' => $store->id,
                'user_id' => $store->user_id,
                'name' => $store->name,
                'email' => $store->email,
                'phone' => $store->phone,
                'whatsapp' => $store->whatsapp,
                'logo' => $store->logo,
                'logo_url' => $store->logo_url,
                'description' => $store->description,
                'created_at' => $store->created_at?->toISOString(),
                'updated_at' => $store->updated_at?->toISOString(),
            ] : null,
        ]);
    }

    public function update(UpdateStoreProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeStoreProfileAccess($user, 'edit store profile');

        $store = Store::query()->where('user_id', $user->id)->first();
        if (! $store) {
            return back()->with('error', 'Data toko untuk akun ini tidak ditemukan.');
        }

        try {
            $this->storeService->updateStore($store, $request->validated(), $request->file('logo'));

            return back()->with('success', 'Profile toko berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui profile toko: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui profile toko: '.$e->getMessage())->withInput();
        }
    }
}
