<?php

namespace App\Repositories\Eloquent;

use App\Models\VariantType;
use App\Models\VariantValue;
use App\Repositories\Contracts\VariantTypeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class VariantTypeRepository implements VariantTypeRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        return VariantType::query()
            ->withCount('values')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?VariantType
    {
        return VariantType::with('values')->find($id);
    }

    public function create(array $data): VariantType
    {
        return VariantType::query()->create($data);
    }

    public function update(VariantType $variantType, array $data): bool
    {
        return $variantType->update($data);
    }

    public function delete(VariantType $variantType): bool
    {
        return $variantType->delete();
    }

    public function syncValues(VariantType $variantType, array $values): void
    {
        // Delete existing values and recreate (simpler than upsert for this case)
        $variantType->values()->delete();

        foreach ($values as $index => $value) {
            if (trim($value) === '') {
                continue;
            }

            VariantValue::create([
                'variant_type_id' => $variantType->id,
                'value' => trim($value),
                'sort_order' => $index,
            ]);
        }
    }
}
