<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductVirtualTryOn;
use App\Repositories\Contracts\ProductVirtualTryOnRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductVirtualTryOnRepository implements ProductVirtualTryOnRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return ProductVirtualTryOn::query()
            ->with([
                'product:id,name,slug,price,primary_image,sku',
                'virtualModel:id,name,gender,photo',
                'user:id,name,email',
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('virtualModel', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when(! empty($filters['product_id']) && $filters['product_id'] !== 'all', function ($query) use ($filters) {
                $query->where('product_id', $filters['product_id']);
            })
            ->when(! empty($filters['user_id']) && $filters['user_id'] !== 'all', function ($query) use ($filters) {
                $query->where('user_id', $filters['user_id']);
            })
            ->when(! empty($filters['model_id']) && $filters['model_id'] !== 'all', function ($query) use ($filters) {
                $query->where('virtual_try_on_model_id', $filters['model_id']);
            })
            ->when(! empty($filters['gender']) && $filters['gender'] !== 'all', function ($query) use ($filters) {
                $query->where('gender', $filters['gender']);
            })
            ->when(isset($filters['without_user']) && ($filters['without_user'] === '1' || $filters['without_user'] === true || $filters['without_user'] === 'true'), function ($query) {
                $query->whereNull('user_id');
            })
            ->when(! empty($filters['user_type']) && $filters['user_type'] !== 'all', function ($query) use ($filters) {
                if ($filters['user_type'] === 'without_user' || $filters['user_type'] === 'default') {
                    $query->whereNull('user_id');
                } elseif ($filters['user_type'] === 'with_user' || $filters['user_type'] === 'user') {
                    $query->whereNotNull('user_id');
                }
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?ProductVirtualTryOn
    {
        return ProductVirtualTryOn::with([
            'product:id,name,slug,price,primary_image,sku',
            'virtualModel:id,name,gender,photo',
            'user:id,name,email',
        ])->find($id);
    }

    public function getDefaultByProductAndGender(int $productId, string $gender, ?int $exceptId = null): ?ProductVirtualTryOn
    {
        return ProductVirtualTryOn::query()
            ->where('product_id', $productId)
            ->whereNull('user_id')
            ->where('gender', $gender)
            ->where('status', '!=', 'failed')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();
    }

    public function create(array $data): ProductVirtualTryOn
    {
        return ProductVirtualTryOn::create($data);
    }

    public function update(ProductVirtualTryOn $tryOn, array $data): bool
    {
        return $tryOn->update($data);
    }

    public function delete(ProductVirtualTryOn $tryOn): bool
    {
        return $tryOn->delete();
    }
}
