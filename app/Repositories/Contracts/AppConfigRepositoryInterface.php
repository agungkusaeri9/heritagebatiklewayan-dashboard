<?php

namespace App\Repositories\Contracts;

use App\Models\AppConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppConfigRepositoryInterface
{
    public function getPaginatedConfigs(int $perPage = 10, ?string $search = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator;

    /**
     * @return Collection<int, AppConfig>
     */
    public function getAll(): Collection;

    public function findById(int $id): ?AppConfig;

    public function findByKey(string $key): ?AppConfig;

    public function create(array $data): AppConfig;

    public function update(AppConfig $appConfig, array $data): bool;

    public function delete(AppConfig $appConfig): bool;
}
