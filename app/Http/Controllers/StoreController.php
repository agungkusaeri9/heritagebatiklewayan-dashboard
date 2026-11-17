<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\IndexStoreRequest;
use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(
        private StoreService $storeService
    ) {}

    private function isStoreAdminOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole(StoreService::ADMIN_STORE_ROLE)
            && ! $user->hasAnyRole(['super admin', 'admin']);
    }

    private function authorizeStoreDataAccess(?User $user, string $permission): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('super admin')) {
            return;
        }

        if ($this->isStoreAdminOnly($user)) {
            abort(403, 'Role Admin Store tidak diperbolehkan mengakses Data Toko.');
        }

        if ($user->roles()->exists() && ! $user->can($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses Data Toko.');
        }
    }

    public function index(IndexStoreRequest $request): Response
    {
        $this->authorizeStoreDataAccess($request->user(), 'view stores');

        $stores = $this->storeService->getStores($request->filters(), $request->perPage());

        return Inertia::render('stores/Index', [
            'stores' => $stores->through(fn (Store $store) => [
                'id' => $store->id,
                'user_id' => $store->user_id,
                'name' => $store->name,
                'email' => $store->email,
                'phone' => $store->phone,
                'whatsapp' => $store->whatsapp,
                'address' => $store->address,
                'logo' => $store->logo,
                'logo_url' => $store->logo_url,
                'description' => $store->description,
                'created_at' => $store->created_at?->toISOString(),
                'updated_at' => $store->updated_at?->toISOString(),
                'user' => $store->user ? [
                    'id' => $store->user->id,
                    'name' => $store->user->name,
                    'username' => $store->user->username,
                    'email' => $store->user->email,
                    'roles' => $store->user->roles->pluck('name')->values()->toArray(),
                ] : null,
            ]),
            'filters' => $request->filters(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeStoreDataAccess($request->user(), 'create stores');

        return Inertia::render('stores/Create');
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $this->authorizeStoreDataAccess($request->user(), 'create stores');

        $validated = $request->validated();

        try {
            $this->storeService->createStore($validated, $request->file('logo'));

            return redirect()->route('stores.index')->with('success', 'Toko dan akun Admin Store berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan toko: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menambahkan toko: '.$e->getMessage())->withInput();
        }
    }

    public function edit(Request $request, Store $store): Response
    {
        $this->authorizeStoreDataAccess($request->user(), 'edit stores');

        $store->loadMissing(['user.roles']);

        return Inertia::render('stores/Edit', [
            'store' => [
                'id' => $store->id,
                'user_id' => $store->user_id,
                'name' => $store->name,
                'email' => $store->email,
                'phone' => $store->phone,
                'whatsapp' => $store->whatsapp,
                'address' => $store->address,
                'logo' => $store->logo,
                'logo_url' => $store->logo_url,
                'description' => $store->description,
                'created_at' => $store->created_at?->toISOString(),
                'updated_at' => $store->updated_at?->toISOString(),
                'user' => $store->user ? [
                    'id' => $store->user->id,
                    'name' => $store->user->name,
                    'username' => $store->user->username,
                    'email' => $store->user->email,
                    'roles' => $store->user->roles->pluck('name')->values()->toArray(),
                ] : null,
            ],
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreDataAccess($request->user(), 'edit stores');

        $validated = $request->validated();

        try {
            $this->storeService->updateStore($store, $validated, $request->file('logo'));

            return redirect()->route('stores.index')->with('success', 'Data toko dan akun Admin Store berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui toko: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui toko: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreDataAccess($request->user(), 'delete stores');

        try {
            $this->storeService->deleteStore($store);

            return back()->with('success', 'Toko berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus toko: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus toko: '.$e->getMessage());
        }
    }
}
