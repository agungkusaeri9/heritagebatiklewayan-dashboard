<?php

namespace App\Repositories\Contracts;

use App\Models\VirtualTryOnModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VirtualTryOnModelRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?VirtualTryOnModel;

    public function create(array $data): VirtualTryOnModel;

    public function update(VirtualTryOnModel $model, array $data): bool;

    public function delete(VirtualTryOnModel $model): bool;
}
