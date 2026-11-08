<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class TestFcmNotificationSeeder extends Seeder
{
    /**
     * Run a test seeder to create a general notification and trigger FCM push notification.
     */
    public function run(): void
    {
        $setting = NotificationSetting::where('code', 'GENERAL_ANNOUNCEMENT')->first();

        $notificationService = app(NotificationService::class);

        $notification = $notificationService->createNotification([
            'notification_setting_id' => $setting?->id,
            'title' => 'Test Push Notification FCM 🚀',
            'message' => 'Ini adalah pengumuman tes otomatis dari seeder backend yang langsung mentrigger FCM Push Notification ke aplikasi mobile.',
            'recipient_type' => 'ALL_USERS',
        ]);

        echo "Notifikasi tes ID {$notification->id} berhasil dibuat dan dikirim via FCM!\n";
    }
}
