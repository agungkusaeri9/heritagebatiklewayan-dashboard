<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Repositories\Contracts\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AppConfigService
{
    public function __construct(
        protected AppConfigRepositoryInterface $appConfigRepository
    ) {}

    public function getPaginatedConfigs(
        int $perPage = 10,
        ?string $search = null,
        string $sort = 'created_at',
        string $direction = 'desc'
    ): LengthAwarePaginator {
        return $this->appConfigRepository->getPaginatedConfigs($perPage, $search, $sort, $direction);
    }

    /**
     * @return Collection<int, AppConfig>
     */
    public function getAllConfigs(): Collection
    {
        return $this->appConfigRepository->getAll();
    }

    public function getConfigById(int $id): ?AppConfig
    {
        return $this->appConfigRepository->findById($id);
    }

    public function getConfigByKey(string $key): ?AppConfig
    {
        return $this->appConfigRepository->findByKey($key);
    }

    /**
     * @param  array{key: string, value?: ?string, description?: ?string}  $data
     */
    public function createConfig(array $data): AppConfig
    {
        return $this->appConfigRepository->create($data);
    }

    /**
     * @param  array{key?: string, value?: ?string, description?: ?string}  $data
     */
    public function updateConfig(int $id, array $data): bool
    {
        $config = $this->appConfigRepository->findById($id);

        if (! $config) {
            throw new \RuntimeException('Konfigurasi tidak ditemukan.', 404);
        }

        return $this->appConfigRepository->update($config, $data);
    }

    public function deleteConfig(int $id): bool
    {
        $config = $this->appConfigRepository->findById($id);

        if (! $config) {
            throw new \RuntimeException('Konfigurasi tidak ditemukan.', 404);
        }

        return $this->appConfigRepository->delete($config);
    }

    public function setOrUpdate(string $key, ?string $value, ?string $description = null): AppConfig
    {
        return AppConfig::setValue($key, $value, $description);
    }
}
