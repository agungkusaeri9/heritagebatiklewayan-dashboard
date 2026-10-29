<?php

namespace App\Repositories\Contracts;

use App\Models\ProductVirtualTryOn;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductVirtualTryOnRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?ProductVirtualTryOn;

    public function getDefaultByProductAndGender(int $productId, string $gender, ?int $exceptId = null): ?ProductVirtualTryOn;

    public function create(array $data): ProductVirtualTryOn;

    public function update(ProductVirtualTryOn $tryOn, array $data): bool;

    public function delete(ProductVirtualTryOn $tryOn): bool;
}
