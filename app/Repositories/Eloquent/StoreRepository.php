<?php

namespace App\Repositories\Eloquent;

use App\Models\Store;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreRepository implements StoreRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage, ?int $userId = null): LengthAwarePaginator
    {
        return Store::query()
            ->with(['user.roles'])
            ->when($userId !== null, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?Store
    {
        return Store::with(['user.roles'])->find($id);
    }

    public function create(array $data): Store
    {
        return Store::query()->create($data);
    }

    public function update(Store $store, array $data): bool
    {
        return $store->update($data);
    }

    public function delete(Store $store): bool
    {
        return $store->delete();
    }
}
