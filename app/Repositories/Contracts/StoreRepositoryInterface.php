<?php

namespace App\Repositories\Contracts;

use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;

interface StoreRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage, ?int $userId = null): LengthAwarePaginator;

    public function findById(int $id): ?Store;

    public function create(array $data): Store;

    public function update(Store $store, array $data): bool;

    public function delete(Store $store): bool;
}
