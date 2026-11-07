<?php

namespace App\Services;

use App\Jobs\GenerateProductVirtualTryOnJob;
use App\Models\AppConfig;
use App\Models\Product;
use App\Models\ProductVirtualTryOn;
use App\Models\User;
use App\Models\VirtualTryOnModel;
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

    /**
     * Get quota and feature status for the given user.
     *
     * @return array<string, mixed>
     */
    public function getUserQuotaStatus(User $user): array
    {
        $dailyLimit = (int) AppConfig::getValue('virtual_try_on_daily_limit_per_user', 3);
        $weeklyLimit = (int) AppConfig::getValue('virtual_try_on_weekly_limit_all_users', 100);

        $dailyUsed = ProductVirtualTryOn::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', '!=', 'failed')
            ->count();

        $weeklyUsed = ProductVirtualTryOn::whereNotNull('user_id')
            ->where('created_at', '>=', now()->startOfWeek())
            ->where('status', '!=', 'failed')
            ->count();

        return [
            'is_feature_enabled' => filter_var(AppConfig::getValue('is_product_virtual_try_on', true), FILTER_VALIDATE_BOOLEAN),
            'is_photo_enabled' => filter_var(AppConfig::getValue('is_product_virtual_try_on_photo', true), FILTER_VALIDATE_BOOLEAN),
            'is_video_enabled' => filter_var(AppConfig::getValue('is_product_virtual_try_on_video', false), FILTER_VALIDATE_BOOLEAN),
            'daily_limit' => $dailyLimit,
            'daily_used' => $dailyUsed,
            'daily_remaining' => max(0, $dailyLimit - $dailyUsed),
            'weekly_limit_all_users' => $weeklyLimit,
            'weekly_used_all_users' => $weeklyUsed,
            'weekly_remaining_all_users' => max(0, $weeklyLimit - $weeklyUsed),
        ];
    }

    /**
     * Create a virtual try-on task requested by an authenticated customer/user.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \InvalidArgumentException
     */
    public function createUserTryOn(array $data, User $user, ?UploadedFile $modelPhoto = null): ProductVirtualTryOn
    {
        $isFeatureEnabled = filter_var(AppConfig::getValue('is_product_virtual_try_on', true), FILTER_VALIDATE_BOOLEAN);
        if (! $isFeatureEnabled) {
            throw new \InvalidArgumentException('Fitur Virtual Try-On saat ini sedang dinonaktifkan.');
        }

        $dailyLimit = (int) AppConfig::getValue('virtual_try_on_daily_limit_per_user', 3);
        if ($dailyLimit > 0) {
            $userTodayCount = ProductVirtualTryOn::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->where('status', '!=', 'failed')
                ->count();
            if ($userTodayCount >= $dailyLimit) {
                throw new \InvalidArgumentException("Batas kuota harian Virtual Try-On Anda telah tercapai (maksimal {$dailyLimit} kali per hari).");
            }
        }

        $weeklyLimit = (int) AppConfig::getValue('virtual_try_on_weekly_limit_all_users', 100);
        if ($weeklyLimit > 0) {
            $weeklyCount = ProductVirtualTryOn::whereNotNull('user_id')
                ->where('created_at', '>=', now()->startOfWeek())
                ->where('status', '!=', 'failed')
                ->count();
            if ($weeklyCount >= $weeklyLimit) {
                throw new \InvalidArgumentException('Batas kuota mingguan Virtual Try-On sistem telah tercapai. Silakan coba kembali minggu depan.');
            }
        }

        $productId = (int) $data['product_id'];
        $product = Product::with('images')->find($productId);
        if (! $product || ! $product->is_active) {
            throw new \InvalidArgumentException('Produk tidak ditemukan atau tidak aktif.');
        }

        $productImage = $product->primary_image ?: $product->images->first()?->image;
        if (empty($productImage)) {
            throw new \InvalidArgumentException('Produk belum memiliki foto yang dapat digunakan untuk Virtual Try-On.');
        }

        $virtualModel = null;
        if ($modelPhoto instanceof UploadedFile) {
            $storedPath = $modelPhoto->store('virtual-try-on-models/user-uploads', 'public');
            $virtualModel = VirtualTryOnModel::create([
                'name' => 'Foto Galeri - '.$user->name,
                'gender' => $data['gender'] ?? 'unisex',
                'photo' => $storedPath,
                'is_active' => false,
            ]);
        } else {
            $modelId = (int) ($data['virtual_try_on_model_id'] ?? 0);
            $virtualModel = VirtualTryOnModel::active()->find($modelId);
            if (! $virtualModel || empty($virtualModel->photo)) {
                throw new \InvalidArgumentException('Model avatar mannequin tidak ditemukan atau tidak aktif.');
            }

            // Check if there is already an active job for this product & model
            $pending = ProductVirtualTryOn::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->where('virtual_try_on_model_id', $modelId)
                ->whereIn('status', ['queued', 'processing'])
                ->first();
            if ($pending) {
                throw new \InvalidArgumentException('Permintaan Virtual Try-On untuk produk dan model ini sedang diproses dalam antrean.');
            }
        }

        $videoConfig = AppConfig::getValue('is_product_virtual_try_on_video', false);
        $videoEnabled = filter_var($videoConfig, FILTER_VALIDATE_BOOLEAN);
        $generateVideo = isset($data['generate_video'])
            ? (filter_var($data['generate_video'], FILTER_VALIDATE_BOOLEAN) && $videoEnabled)
            : $videoEnabled;

        $tryOn = DB::transaction(function () use ($productId, $virtualModel, $user, $generateVideo) {
            $tryOn = $this->tryOnRepository->create([
                'product_id' => $productId,
                'virtual_try_on_model_id' => $virtualModel->id,
                'user_id' => $user->id,
                'gender' => $virtualModel->gender,
                'generate_video' => $generateVideo,
                'status' => 'queued',
                'progress' => 5,
                'step_label' => 'Menunggu antrean...',
                'photo' => null,
                'video' => null,
            ]);

            GenerateProductVirtualTryOnJob::dispatch($tryOn->id);

            $this->activityLogService->log(
                'Create User Product Virtual Try-On',
                "User #{$user->id} dispatched virtual try-on #{$tryOn->id} for product #{$productId}",
                [
                    'try_on_id' => $tryOn->id,
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'virtual_try_on_model_id' => $virtualModel->id,
                    'generate_video' => $generateVideo,
                ]
            );

            return $tryOn;
        });

        $this->triggerBackgroundQueueWorkerIfNeeded();

        return $tryOn;
    }

    /**
     * Trigger a non-blocking background queue worker process if queue driver is database.
     */
    private function triggerBackgroundQueueWorkerIfNeeded(): void
    {
        try {
            if (config('queue.default') !== 'database') {
                return;
            }

            $artisan = base_path('artisan');
            if (PHP_OS_FAMILY === 'Windows') {
                $cmd = 'start /B php "'.$artisan.'" queue:work --once --tries=1 --timeout=600 > NUL 2>&1';
                @pclose(@popen($cmd, 'r'));
            } else {
                $cmd = 'php "'.$artisan.'" queue:work --once --tries=1 --timeout=600 > /dev/null 2>&1 &';
                @exec($cmd);
            }
        } catch (\Throwable) {
            // Ignore if background process spawning is restricted
        }
    }

    public function retryGeneration(ProductVirtualTryOn $tryOn): bool
    {
        $result = DB::transaction(function () use ($tryOn) {
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

        $this->triggerBackgroundQueueWorkerIfNeeded();

        return $result;
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
