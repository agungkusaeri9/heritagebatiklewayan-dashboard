<?php

namespace App\Repositories\Eloquent;

use App\Models\VirtualTryOnModel;
use App\Repositories\Contracts\VirtualTryOnModelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VirtualTryOnModelRepository implements VirtualTryOnModelRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return VirtualTryOnModel::query()
            ->withCount('tryOns')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($filters['gender'] ?? null, function ($query, $gender) {
                if ($gender !== 'all' && $gender !== '') {
                    $query->where('gender', $gender);
                }
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all', function ($query) use ($filters) {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAllActive(): Collection
    {
        return VirtualTryOnModel::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?VirtualTryOnModel
    {
        return VirtualTryOnModel::withCount('tryOns')->find($id);
    }

    public function create(array $data): VirtualTryOnModel
    {
        return VirtualTryOnModel::query()->create($data);
    }

    public function update(VirtualTryOnModel $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(VirtualTryOnModel $model): bool
    {
        return $model->delete();
    }
}
