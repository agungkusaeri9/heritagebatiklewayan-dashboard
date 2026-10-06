<?php

namespace App\Repositories\Eloquent;

use App\Models\NotificationSetting;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationSettingRepository implements NotificationSettingRepositoryInterface
{
    public function getAllActive(): Collection
    {
        return NotificationSetting::where('is_active', true)->orderBy('name')->get();
    }

    public function getAll(): Collection
    {
        return NotificationSetting::orderBy('name')->get();
    }

    public function findById(int $id): ?NotificationSetting
    {
        return NotificationSetting::find($id);
    }

    public function findByCode(string $code): ?NotificationSetting
    {
        return NotificationSetting::where('code', $code)->first();
    }

    public function create(array $data): NotificationSetting
    {
        return NotificationSetting::create($data);
    }

    public function update(NotificationSetting $setting, array $data): bool
    {
        return $setting->update($data);
    }

    public function delete(NotificationSetting $setting): bool
    {
        return $setting->delete();
    }
}
