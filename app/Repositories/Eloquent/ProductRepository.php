<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get paginated products with filtering and sorting.
     */
    public function getPaginatedProducts(
        int $perPage = 10,
        ?string $search = null,
        ?string $status = null,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc',
        int|string|null $category = null
    ): LengthAwarePaginator {
        $query = Product::query()->with(['category', 'images', 'variantValues.variantType']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== 'all') {
            $isActive = $status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($category !== null && $category !== '' && $category !== 'all') {
            if (is_numeric($category)) {
                $query->where('category_id', (int) $category);
            } else {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category);
                });
            }
        }

        $allowedSortColumns = ['name', 'price', 'stock', 'is_active', 'created_at'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product
    {
        return Product::with(['category', 'images', 'variantValues.variantType'])->find($id);
    }

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['category', 'images', 'variantValues.variantType'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Count total products.
     */
    public function countProducts(): int
    {
        return Product::count();
    }
}
