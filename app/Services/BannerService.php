<?php

namespace App\Services;

use App\Models\Banner;
use App\Repositories\Contracts\BannerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    public function __construct(
        private BannerRepositoryInterface $bannerRepository,
        private ActivityLogService $activityLogService
    ) {}

    public function getBanners(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->bannerRepository->getAllPaginated($filters, $perPage);
    }

    public function getActiveBanners(): Collection
    {
        return $this->bannerRepository->getAllActive();
    }

    public function getBannerById(int $id): ?Banner
    {
        return $this->bannerRepository->findById($id);
    }

    public function createBanner(array $data, ?UploadedFile $image = null): Banner
    {
        return DB::transaction(function () use ($data, $image) {
            if ($image instanceof UploadedFile) {
                $data['image'] = $image->store('banners', 'public');
            }

            // Clean up foreign keys based on target_type
            if (($data['target_type'] ?? 'none') !== 'product') {
                $data['product_id'] = null;
            }
            if (($data['target_type'] ?? 'none') !== 'category') {
                $data['category_id'] = null;
            }
            if (($data['target_type'] ?? 'none') !== 'url') {
                $data['target_url'] = null;
            }

            $banner = $this->bannerRepository->create($data);

            if (! $banner) {
                throw new \Exception('Gagal menyimpan data banner ke database.');
            }

            $this->activityLogService->log(
                'Create Banner',
                "Created banner: {$banner->title}",
                ['banner_id' => $banner->id, 'title' => $banner->title]
            );

            return $banner;
        });
    }

    public function updateBanner(Banner $banner, array $data, ?UploadedFile $image = null): bool
    {
        return DB::transaction(function () use ($banner, $data, $image) {
            $existingBanner = $this->bannerRepository->findById($banner->id);
            if (! $existingBanner) {
                throw new \Exception('Data banner tidak ditemukan di sistem.');
            }

            $oldData = $existingBanner->only(['title', 'image', 'target_type', 'target_url', 'product_id', 'category_id', 'sort_order', 'is_active', 'start_date', 'end_date']);

            if ($image instanceof UploadedFile) {
                if ($existingBanner->image && Storage::disk('public')->exists($existingBanner->image)) {
                    Storage::disk('public')->delete($existingBanner->image);
                }
                $data['image'] = $image->store('banners', 'public');
            } else {
                unset($data['image']);
            }

            // Clean up foreign keys based on target_type
            if (($data['target_type'] ?? $existingBanner->target_type) !== 'product') {
                $data['product_id'] = null;
            }
            if (($data['target_type'] ?? $existingBanner->target_type) !== 'category') {
                $data['category_id'] = null;
            }
            if (($data['target_type'] ?? $existingBanner->target_type) !== 'url') {
                $data['target_url'] = null;
            }

            $updated = $this->bannerRepository->update($existingBanner, $data);

            if (! $updated) {
                throw new \Exception('Gagal memperbarui data banner ke database.');
            }

            $this->activityLogService->log(
                'Update Banner',
                "Updated banner: {$existingBanner->title}",
                [
                    'banner_id' => $existingBanner->id,
                    'old' => $oldData,
                    'new' => $existingBanner->fresh()->only(['title', 'image', 'target_type', 'target_url', 'product_id', 'category_id', 'sort_order', 'is_active', 'start_date', 'end_date']),
                ]
            );

            return true;
        });
    }

    public function deleteBanner(Banner $banner): bool
    {
        return DB::transaction(function () use ($banner) {
            $existingBanner = $this->bannerRepository->findById($banner->id);
            if (! $existingBanner) {
                throw new \Exception('Data banner tidak ditemukan di sistem.');
            }

            $bannerTitle = $existingBanner->title;
            $bannerId = $existingBanner->id;
            $bannerImage = $existingBanner->image;

            $deleted = $this->bannerRepository->delete($existingBanner);

            if (! $deleted) {
                throw new \Exception('Gagal menghapus data banner dari database.');
            }

            if ($bannerImage && Storage::disk('public')->exists($bannerImage)) {
                Storage::disk('public')->delete($bannerImage);
            }

            $this->activityLogService->log(
                'Delete Banner',
                "Deleted banner: {$bannerTitle}",
                ['deleted_banner_id' => $bannerId]
            );

            return true;
        });
    }

    public function toggleBannerStatus(Banner $banner): bool
    {
        $existingBanner = $this->bannerRepository->findById($banner->id);
        if (! $existingBanner) {
            throw new \Exception('Data banner tidak ditemukan di sistem.');
        }

        $toggled = $this->bannerRepository->toggleActive($existingBanner);

        $status = $existingBanner->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->activityLogService->log(
            'Toggle Banner Status',
            "Banner '{$existingBanner->title}' telah {$status}",
            ['banner_id' => $existingBanner->id, 'is_active' => $existingBanner->fresh()->is_active]
        );

        return $toggled;
    }
}
