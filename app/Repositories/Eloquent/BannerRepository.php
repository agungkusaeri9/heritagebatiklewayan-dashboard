<?php

namespace App\Repositories\Eloquent;

use App\Models\Banner;
use App\Repositories\Contracts\BannerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BannerRepository implements BannerRepositoryInterface
{
    /**
     * Get paginated banners with search and status filters.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Banner::query()
            ->with(['product:id,name,primary_image', 'category:id,name']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            $isActive = $filters['status'] === 'active' || $filters['status'] === '1' || $filters['status'] === true;
            $query->where('is_active', $isActive);
        }

        if (! empty($filters['target_type']) && $filters['target_type'] !== 'all') {
            $query->where('target_type', $filters['target_type']);
        }

        $sortColumn = $filters['sort'] ?? 'sort_order';
        $sortDirection = $filters['direction'] ?? 'asc';

        $allowedSortColumns = ['title', 'sort_order', 'is_active', 'start_date', 'end_date', 'created_at'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get all active banners ordered by sort_order for mobile API / carousel.
     */
    public function getAllActive(): Collection
    {
        return Banner::query()
            ->active()
            ->with(['product:id,name,slug,primary_image,price', 'category:id,name,slug'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a banner by ID with relationships.
     */
    public function findById(int $id): ?Banner
    {
        return Banner::with(['product', 'category'])->find($id);
    }

    /**
     * Create a new banner.
     */
    public function create(array $data): Banner
    {
        return Banner::create($data);
    }

    /**
     * Update an existing banner.
     */
    public function update(Banner $banner, array $data): bool
    {
        return $banner->update($data);
    }

    /**
     * Delete a banner.
     */
    public function delete(Banner $banner): bool
    {
        return $banner->delete();
    }

    /**
     * Toggle active status of a banner.
     */
    public function toggleActive(Banner $banner): bool
    {
        return $banner->update(['is_active' => ! $banner->is_active]);
    }
}
