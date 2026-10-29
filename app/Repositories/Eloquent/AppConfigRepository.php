<?php

namespace App\Repositories\Eloquent;

use App\Models\AppConfig;
use App\Repositories\Contracts\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AppConfigRepository implements AppConfigRepositoryInterface
{
    public function getPaginatedConfigs(int $perPage = 10, ?string $search = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator
    {
        $query = AppConfig::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, AppConfig>
     */
    public function getAll(): Collection
    {
        return AppConfig::query()->orderBy('key', 'asc')->get();
    }

    public function findById(int $id): ?AppConfig
    {
        return AppConfig::find($id);
    }

    public function findByKey(string $key): ?AppConfig
    {
        return AppConfig::where('key', $key)->first();
    }

    public function create(array $data): AppConfig
    {
        return AppConfig::create($data);
    }

    public function update(AppConfig $appConfig, array $data): bool
    {
        return $appConfig->update($data);
    }

    public function delete(AppConfig $appConfig): bool
    {
        return $appConfig->delete();
    }
}
