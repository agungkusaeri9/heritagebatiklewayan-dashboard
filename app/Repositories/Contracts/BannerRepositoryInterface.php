<?php

namespace App\Repositories\Contracts;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BannerRepositoryInterface
{
    /**
     * Get paginated banners with search and status filters.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all active banners ordered by sort_order for mobile API / carousel.
     */
    public function getAllActive(): Collection;

    /**
     * Find a banner by ID with relationships.
     */
    public function findById(int $id): ?Banner;

    /**
     * Create a new banner.
     */
    public function create(array $data): Banner;

    /**
     * Update an existing banner.
     */
    public function update(Banner $banner, array $data): bool;

    /**
     * Delete a banner.
     */
    public function delete(Banner $banner): bool;

    /**
     * Toggle active status of a banner.
     */
    public function toggleActive(Banner $banner): bool;
}
