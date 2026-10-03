<?php

namespace App\Services;

use App\Models\VariantType;
use App\Repositories\Contracts\VariantTypeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariantTypeService
{
    public function __construct(
        private VariantTypeRepositoryInterface $variantTypeRepository,
        private ActivityLogService $activityLogService
    ) {}

    public function getVariantTypes(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->variantTypeRepository->getAllPaginated($filters, $perPage);
    }

    public function getVariantTypeById(int $id): ?VariantType
    {
        return $this->variantTypeRepository->findById($id);
    }

    public function createVariantType(array $data, array $values = []): VariantType
    {
        return DB::transaction(function () use ($data, $values) {
            $data['slug'] = empty($data['slug']) ? Str::slug($data['name']) : Str::slug($data['slug']);

            $variantType = $this->variantTypeRepository->create($data);

            if (! $variantType) {
                throw new \Exception('Gagal menyimpan data varian ke database.');
            }

            if (! empty($values)) {
                $this->variantTypeRepository->syncValues($variantType, $values);
            }

            $this->activityLogService->log(
                'Create Variant Type',
                "Created variant type: {$variantType->name}",
                ['variant_type_id' => $variantType->id, 'values_count' => count($values)]
            );

            return $variantType->load('values');
        });
    }

    public function updateVariantType(VariantType $variantType, array $data): bool
    {
        return DB::transaction(function () use ($variantType, $data) {
            $existing = $this->variantTypeRepository->findById($variantType->id);
            if (! $existing) {
                throw new \Exception('Data varian tidak ditemukan di sistem.');
            }

            $oldName = $existing->name;

            $data['slug'] = empty($data['slug']) ? Str::slug($data['name']) : Str::slug($data['slug']);

            $updated = $this->variantTypeRepository->update($existing, $data);

            if (! $updated) {
                throw new \Exception('Gagal memperbarui data varian ke database.');
            }

            $this->activityLogService->log(
                'Update Variant Type',
                "Updated variant type: {$oldName}",
                ['variant_type_id' => $existing->id, 'old_name' => $oldName, 'new_name' => $existing->fresh()?->name]
            );

            return true;
        });
    }

    public function syncValues(VariantType $variantType, array $values): void
    {
        DB::transaction(function () use ($variantType, $values) {
            $existing = $this->variantTypeRepository->findById($variantType->id);
            if (! $existing) {
                throw new \Exception('Data varian tidak ditemukan di sistem.');
            }

            $oldCount = $existing->values()->count();

            $this->variantTypeRepository->syncValues($existing, $values);

            $this->activityLogService->log(
                'Sync Variant Values',
                "Synced values for variant type: {$existing->name}",
                [
                    'variant_type_id' => $existing->id,
                    'old_count' => $oldCount,
                    'new_count' => count(array_filter($values, fn ($v) => trim($v) !== '')),
                ]
            );
        });
    }

    public function deleteVariantType(VariantType $variantType): bool
    {
        return DB::transaction(function () use ($variantType) {
            $existing = $this->variantTypeRepository->findById($variantType->id);
            if (! $existing) {
                throw new \Exception('Data varian tidak ditemukan di sistem.');
            }

            $name = $existing->name;
            $id = $existing->id;

            $deleted = $this->variantTypeRepository->delete($existing);

            if (! $deleted) {
                throw new \Exception('Gagal menghapus data varian dari database.');
            }

            $this->activityLogService->log(
                'Delete Variant Type',
                "Deleted variant type: {$name}",
                ['deleted_variant_type_id' => $id]
            );

            return true;
        });
    }
}
