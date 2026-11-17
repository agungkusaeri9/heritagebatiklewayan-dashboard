<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StoreService
{
    public const ADMIN_STORE_ROLE = 'admin store';

    public function __construct(
        private StoreRepositoryInterface $storeRepository,
        private ActivityLogService $activityLogService
    ) {}

    public function getStores(array $filters, int $perPage = 10, ?int $userId = null): LengthAwarePaginator
    {
        return $this->storeRepository->getAllPaginated($filters, $perPage, $userId);
    }

    public function getStoreById(int $id): ?Store
    {
        return $this->storeRepository->findById($id);
    }

    public function createStore(array $data, ?UploadedFile $logo = null): Store
    {
        return DB::transaction(function () use ($data, $logo) {
            $adminStoreRole = Role::findOrCreate(self::ADMIN_STORE_ROLE, 'web');

            $seller = User::create([
                'name' => $data['seller_name'],
                'username' => $data['seller_username'],
                'email' => $data['seller_email'] ?? null,
                'password' => $data['seller_password'],
                'email_verified_at' => now(),
            ]);
            $seller->assignRole($adminStoreRole);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $storeData = [
                'user_id' => $seller->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'],
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            if ($logo instanceof UploadedFile) {
                $storeData['logo'] = $logo->store('stores', 'public');
            }

            $store = $this->storeRepository->create($storeData);

            if (! $store) {
                throw new \Exception('Gagal menyimpan data toko ke database.');
            }

            $this->activityLogService->log(
                'Create Store',
                "Created store: {$store->name} (Admin Store: {$seller->username})",
                [
                    'store_id' => $store->id,
                    'user_id' => $seller->id,
                ]
            );

            return $store->load('user.roles');
        });
    }

    public function updateStore(Store $store, array $data, ?UploadedFile $logo = null): Store
    {
        return DB::transaction(function () use ($store, $data, $logo) {
            $existingStore = $this->storeRepository->findById($store->id);
            if (! $existingStore) {
                throw new \Exception('Data toko tidak ditemukan di sistem.');
            }

            $adminStoreRole = Role::findOrCreate(self::ADMIN_STORE_ROLE, 'web');

            if ($existingStore->user && (array_key_exists('seller_name', $data) || array_key_exists('seller_username', $data))) {
                $sellerUpdateData = [
                    'name' => $data['seller_name'],
                    'username' => $data['seller_username'],
                    'email' => $data['seller_email'] ?? null,
                ];
                if (! empty($data['seller_password'])) {
                    $sellerUpdateData['password'] = $data['seller_password'];
                }
                $existingStore->user->update($sellerUpdateData);
                if (! $existingStore->user->hasRole(self::ADMIN_STORE_ROLE, 'web')) {
                    $existingStore->user->assignRole($adminStoreRole);
                }

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }

            $storeData = [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'],
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            if ($logo instanceof UploadedFile) {
                $newPath = $logo->store('stores', 'public');
                if ($existingStore->logo && Storage::disk('public')->exists($existingStore->logo)) {
                    Storage::disk('public')->delete($existingStore->logo);
                }
                $storeData['logo'] = $newPath;
            }

            $oldData = $existingStore->only(['name', 'email', 'phone', 'whatsapp', 'address', 'user_id']);

            $updated = $this->storeRepository->update($existingStore, $storeData);
            if (! $updated) {
                throw new \Exception('Gagal memperbarui data toko di database.');
            }

            $freshStore = $existingStore->fresh(['user.roles']);

            $this->activityLogService->log(
                'Update Store',
                "Updated store: {$freshStore->name}",
                [
                    'store_id' => $freshStore->id,
                    'old' => $oldData,
                    'new' => $freshStore->only(['name', 'email', 'phone', 'whatsapp', 'address', 'user_id']),
                ]
            );

            return $freshStore;
        });
    }

    public function deleteStore(Store $store): bool
    {
        return DB::transaction(function () use ($store) {
            $existingStore = $this->storeRepository->findById($store->id);
            if (! $existingStore) {
                throw new \Exception('Data toko tidak ditemukan di sistem.');
            }

            $storeName = $existingStore->name;
            $storeId = $existingStore->id;
            $storeLogo = $existingStore->logo;

            $deleted = $this->storeRepository->delete($existingStore);

            if (! $deleted) {
                throw new \Exception('Gagal menghapus data toko dari database.');
            }

            if ($storeLogo && Storage::disk('public')->exists($storeLogo)) {
                Storage::disk('public')->delete($storeLogo);
            }

            $this->activityLogService->log(
                'Delete Store',
                "Deleted store: {$storeName}",
                ['deleted_store_id' => $storeId]
            );

            return true;
        });
    }
}
