<?php

namespace App\Services;

use App\Jobs\GenerateProductVirtualTryOnJob;
use App\Models\ProductVirtualTryOn;
use App\Repositories\Contracts\ProductVirtualTryOnRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVirtualTryOnService
{
    public function __construct(
        private ProductVirtualTryOnRepositoryInterface $tryOnRepository,
        private ActivityLogService $activityLogService
    ) {}

    public function getTryOns(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->tryOnRepository->getAllPaginated($filters, $perPage);
    }

    public function getTryOnById(int $id): ?ProductVirtualTryOn
    {
        return $this->tryOnRepository->findById($id);
    }

    public function createDefaultTryOn(array $data): ProductVirtualTryOn
    {
        return DB::transaction(function () use ($data) {
            $productId = (int) $data['product_id'];
            $gender = $data['gender'];

            // Check constraint: only 1 female and 1 male per product for default showcase (excluding failed)
            $existing = $this->tryOnRepository->getDefaultByProductAndGender($productId, $gender);
            if ($existing) {
                $genderLabel = $gender === 'male' ? 'Laki-laki (Pria)' : 'Perempuan (Wanita)';
                throw new \InvalidArgumentException("Produk ini sudah memiliki model default untuk gender {$genderLabel}. Maksimal hanya 1 model pria dan 1 model wanita per produk.");
            }

            // Always null for default product showcase
            $data['user_id'] = null;
            $data['status'] = 'queued';
            $data['progress'] = 5;
            $data['step_label'] = 'Menunggu antrean...';
            $data['generate_video'] = (bool) ($data['generate_video'] ?? true);
            $data['photo'] = null;
            $data['video'] = null;

            $tryOn = $this->tryOnRepository->create($data);

            GenerateProductVirtualTryOnJob::dispatch($tryOn->id);

            $this->activityLogService->log(
                'Create Default Product Virtual Try-On',
                "Dispatched generation job for default virtual try-on #{$tryOn->id} ({$gender}) for product #{$tryOn->product_id}",
                [
                    'try_on_id' => $tryOn->id,
                    'product_id' => $tryOn->product_id,
                    'virtual_try_on_model_id' => $tryOn->virtual_try_on_model_id,
                    'gender' => $tryOn->gender,
                ]
            );

            return $tryOn;
        });
    }

    public function retryGeneration(ProductVirtualTryOn $tryOn): bool
    {
        return DB::transaction(function () use ($tryOn) {
            $this->tryOnRepository->update($tryOn, [
                'status' => 'queued',
                'progress' => 5,
                'step_label' => 'Menunggu antrean...',
                'error_message' => null,
            ]);

            GenerateProductVirtualTryOnJob::dispatch($tryOn->id);

            $this->activityLogService->log(
                'Retry Default Product Virtual Try-On',
                "Retried generation job for virtual try-on #{$tryOn->id}",
                ['try_on_id' => $tryOn->id]
            );

            return true;
        });
    }

    public function updateDefaultTryOn(ProductVirtualTryOn $tryOn, array $data, ?UploadedFile $photo = null, ?UploadedFile $video = null): bool
    {
        return DB::transaction(function () use ($tryOn, $data, $photo, $video) {
            $productId = (int) ($data['product_id'] ?? $tryOn->product_id);
            $gender = $data['gender'] ?? $tryOn->gender;

            if ($gender) {
                $existing = $this->tryOnRepository->getDefaultByProductAndGender($productId, $gender, $tryOn->id);
                if ($existing) {
                    $genderLabel = $gender === 'male' ? 'Laki-laki (Pria)' : 'Perempuan (Wanita)';
                    throw new \InvalidArgumentException("Produk ini sudah memiliki model default untuk gender {$genderLabel}.");
                }
            }

            if ($photo instanceof UploadedFile) {
                if ($tryOn->photo && ! Str::startsWith($tryOn->photo, ['http://', 'https://']) && Storage::disk('public')->exists($tryOn->photo)) {
                    Storage::disk('public')->delete($tryOn->photo);
                }
                $data['photo'] = $photo->store('product-virtual-try-ons/photos', 'public');
            } else {
                unset($data['photo']);
            }

            if ($video instanceof UploadedFile) {
                if ($tryOn->video && ! Str::startsWith($tryOn->video, ['http://', 'https://']) && Storage::disk('public')->exists($tryOn->video)) {
                    Storage::disk('public')->delete($tryOn->video);
                }
                $data['video'] = $video->store('product-virtual-try-ons/videos', 'public');
            } else {
                unset($data['video']);
            }

            $updated = $this->tryOnRepository->update($tryOn, $data);

            $this->activityLogService->log(
                'Update Default Product Virtual Try-On',
                "Updated default virtual try-on #{$tryOn->id} for product #{$tryOn->product_id}",
                [
                    'try_on_id' => $tryOn->id,
                    'product_id' => $tryOn->product_id,
                ]
            );

            return $updated;
        });
    }

    public function deleteTryOn(ProductVirtualTryOn $tryOn): bool
    {
        return DB::transaction(function () use ($tryOn) {
            // Delete local photo if not external URL
            if ($tryOn->photo && ! Str::startsWith($tryOn->photo, ['http://', 'https://']) && Storage::disk('public')->exists($tryOn->photo)) {
                Storage::disk('public')->delete($tryOn->photo);
            }

            // Delete local video if not external URL
            if ($tryOn->video && ! Str::startsWith($tryOn->video, ['http://', 'https://']) && Storage::disk('public')->exists($tryOn->video)) {
                Storage::disk('public')->delete($tryOn->video);
            }

            $deleted = $this->tryOnRepository->delete($tryOn);

            $this->activityLogService->log(
                'Delete Product Virtual Try-On',
                "Deleted virtual try-on #{$tryOn->id} for product #{$tryOn->product_id}",
                [
                    'try_on_id' => $tryOn->id,
                    'product_id' => $tryOn->product_id,
                    'user_id' => $tryOn->user_id,
                ]
            );

            return $deleted;
        });
    }
}
