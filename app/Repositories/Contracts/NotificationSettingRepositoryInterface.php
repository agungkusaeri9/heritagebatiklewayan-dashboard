<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Collection;

interface NotificationSettingRepositoryInterface
{
    /**
     * Get all active notification settings.
     */
    public function getAllActive(): Collection;

    /**
     * Get all notification settings.
     */
    public function getAll(): Collection;

    /**
     * Find by ID.
     */
    public function findById(int $id): ?NotificationSetting;

    /**
     * Find by code.
     */
    public function findByCode(string $code): ?NotificationSetting;

    /**
     * Create setting.
     */
    public function create(array $data): NotificationSetting;

    /**
     * Update setting.
     */
    public function update(NotificationSetting $setting, array $data): bool;

    /**
     * Delete setting.
     */
    public function delete(NotificationSetting $setting): bool;
}
