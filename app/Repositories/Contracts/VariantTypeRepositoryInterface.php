<?php

namespace App\Repositories\Contracts;

use App\Models\VariantType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VariantTypeRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?VariantType;

    public function create(array $data): VariantType;

    public function update(VariantType $variantType, array $data): bool;

    public function delete(VariantType $variantType): bool;

    public function syncValues(VariantType $variantType, array $values): void;
}
