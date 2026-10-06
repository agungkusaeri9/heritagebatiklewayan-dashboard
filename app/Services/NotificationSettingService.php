<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationSettingService
{
    public function __construct(
        protected NotificationSettingRepositoryInterface $settingRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getAllSettings(): Collection
    {
        return $this->settingRepository->getAll();
    }

    public function getActiveSettings(): Collection
    {
        return $this->settingRepository->getAllActive();
    }

    public function createSetting(array $data): NotificationSetting
    {
        $setting = $this->settingRepository->create($data);

        $this->activityLogService->log(
            'create',
            "Membuat template pengaturan notifikasi '{$setting->name}' ({$setting->code})",
            ['notification_setting_id' => $setting->id]
        );

        return $setting;
    }

    public function updateSetting(NotificationSetting $setting, array $data): bool
    {
        $updated = $this->settingRepository->update($setting, $data);

        if ($updated) {
            $this->activityLogService->log(
                'update',
                "Memperbarui template pengaturan notifikasi '{$setting->name}' ({$setting->code})",
                ['notification_setting_id' => $setting->id]
            );
        }

        return $updated;
    }

    public function deleteSetting(NotificationSetting $setting): bool
    {
        $name = $setting->name;
        $id = $setting->id;

        $deleted = $this->settingRepository->delete($setting);

        if ($deleted) {
            $this->activityLogService->log(
                'delete',
                "Menghapus template pengaturan notifikasi '{$name}'",
                ['notification_setting_id' => $id]
            );
        }

        return $deleted;
    }
}
