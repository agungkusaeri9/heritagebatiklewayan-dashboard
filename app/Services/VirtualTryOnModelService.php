<?php

namespace App\Services;

use App\Models\VirtualTryOnModel;
use App\Repositories\Contracts\VirtualTryOnModelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VirtualTryOnModelService
{
    public function __construct(
        private VirtualTryOnModelRepositoryInterface $modelRepository,
        private ActivityLogService $activityLogService,
        private FashnAiService $fashnAiService
    ) {}

    public function getModels(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->modelRepository->getAllPaginated($filters, $perPage);
    }

    public function getActiveModels(): Collection
    {
        return $this->modelRepository->getAllActive();
    }

    public function getModelById(int $id): ?VirtualTryOnModel
    {
        return $this->modelRepository->findById($id);
    }

    /**
     * Generate an AI model image using Fashn AI and store it locally.
     *
     * @param  string  $prompt  Prompt description.
     * @return array Contains 'task_id', 'original_url', 'local_path', and 'photo_url'.
     */
    public function generateAiPhoto(string $prompt): array
    {
        return $this->fashnAiService->generateAndSaveModelImage($prompt, 'virtual-try-on-models');
    }

    public function createModel(array $data, ?UploadedFile $photo = null): VirtualTryOnModel
    {
        return DB::transaction(function () use ($data, $photo) {
            if ($photo instanceof UploadedFile) {
                $data['photo'] = $photo->store('virtual-try-on-models', 'public');
            } elseif (! empty($data['generated_photo_path'])) {
                $data['photo'] = $data['generated_photo_path'];
            } elseif (($data['creation_mode'] ?? '') === 'ai' && ! empty($data['prompt'])) {
                $aiResult = $this->fashnAiService->generateAndSaveModelImage($data['prompt'], 'virtual-try-on-models');
                $data['photo'] = $aiResult['local_path'];
            }

            if (empty($data['photo'])) {
                throw new \InvalidArgumentException('Foto model wajib diunggah atau digenerate melalui AI.');
            }

            unset($data['creation_mode'], $data['prompt'], $data['generated_photo_path']);

            $model = $this->modelRepository->create($data);

            $this->activityLogService->log(
                'Create Virtual Try-On Model',
                "Created virtual try-on model: {$model->name}",
                ['model_id' => $model->id, 'name' => $model->name, 'gender' => $model->gender]
            );

            return $model;
        });
    }

    public function updateModel(VirtualTryOnModel $model, array $data, ?UploadedFile $photo = null): bool
    {
        return DB::transaction(function () use ($model, $data, $photo) {
            if ($photo instanceof UploadedFile) {
                if ($model->photo && Storage::disk('public')->exists($model->photo)) {
                    Storage::disk('public')->delete($model->photo);
                }
                $data['photo'] = $photo->store('virtual-try-on-models', 'public');
            } elseif (! empty($data['generated_photo_path'])) {
                if ($model->photo && Storage::disk('public')->exists($model->photo)) {
                    Storage::disk('public')->delete($model->photo);
                }
                $data['photo'] = $data['generated_photo_path'];
            } else {
                unset($data['photo']);
            }

            unset($data['creation_mode'], $data['prompt'], $data['generated_photo_path']);

            $updated = $this->modelRepository->update($model, $data);

            $this->activityLogService->log(
                'Update Virtual Try-On Model',
                "Updated virtual try-on model: {$model->name}",
                ['model_id' => $model->id, 'name' => $model->name]
            );

            return $updated;
        });
    }

    public function deleteModel(VirtualTryOnModel $model): bool
    {
        return DB::transaction(function () use ($model) {
            if ($model->photo && Storage::disk('public')->exists($model->photo)) {
                Storage::disk('public')->delete($model->photo);
            }

            $deleted = $this->modelRepository->delete($model);

            $this->activityLogService->log(
                'Delete Virtual Try-On Model',
                "Deleted virtual try-on model: {$model->name}",
                ['model_id' => $model->id, 'name' => $model->name]
            );

            return $deleted;
        });
    }
}
