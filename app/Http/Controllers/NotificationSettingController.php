<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notification\StoreNotificationSettingRequest;
use App\Http\Requests\Notification\UpdateNotificationSettingRequest;
use App\Models\NotificationSetting;
use App\Services\NotificationSettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingController extends Controller
{
    public function __construct(
        protected NotificationSettingService $settingService
    ) {}

    public function index(): Response
    {
        $settings = $this->settingService->getAllSettings();

        return Inertia::render('notifications/Settings', [
            'settings' => $settings,
        ]);
    }

    public function store(StoreNotificationSettingRequest $request): RedirectResponse
    {
        $this->settingService->createSetting($request->validated());

        return redirect()->route('notification-settings.index')
            ->with('success', 'Template pengaturan notifikasi berhasil ditambahkan.');
    }

    public function update(UpdateNotificationSettingRequest $request, NotificationSetting $notificationSetting): RedirectResponse
    {
        $this->settingService->updateSetting($notificationSetting, $request->validated());

        return redirect()->route('notification-settings.index')
            ->with('success', 'Template pengaturan notifikasi berhasil diperbarui.');
    }

    public function destroy(NotificationSetting $notificationSetting): RedirectResponse
    {
        $this->settingService->deleteSetting($notificationSetting);

        return redirect()->route('notification-settings.index')
            ->with('success', 'Template pengaturan notifikasi berhasil dihapus.');
    }

    public function toggle(NotificationSetting $notificationSetting): RedirectResponse
    {
        $this->settingService->updateSetting($notificationSetting, [
            'is_active' => ! $notificationSetting->is_active,
        ]);

        return redirect()->back()
            ->with('success', "Status template '{$notificationSetting->name}' berhasil diubah.");
    }
}
