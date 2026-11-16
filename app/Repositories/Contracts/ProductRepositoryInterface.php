<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
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
        int|string|null $category = null,
        int|array|null $storeId = null
    ): LengthAwarePaginator;

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Create a new product.
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): bool;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;

    /**
     * Count total products.
     */
    public function countProducts(int|array|null $storeId = null): int;
}
