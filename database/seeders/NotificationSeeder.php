<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'code' => 'GENERAL_ANNOUNCEMENT',
                'name' => 'Pengumuman Umum',
                'description' => 'Notifikasi pengumuman operasional, info libur, atau pembaruan sistem toko.',
                'recipient_type' => 'ALL_USERS',
                'push_enabled' => true,
                'email_enabled' => true,
                'whatsapp_enabled' => false,
                'is_active' => true,
            ],
            [
                'code' => 'PROMO_DISCOUNT',
                'name' => 'Promo & Diskon Spesial',
                'description' => 'Pemberitahuan voucher diskon, promo flash sale, dan penawaran khusus batik.',
                'recipient_type' => 'CUSTOMER',
                'push_enabled' => true,
                'email_enabled' => true,
                'whatsapp_enabled' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ORDER_STATUS_UPDATE',
                'name' => 'Pembaruan Status Pesanan',
                'description' => 'Notifikasi transaksi, resi kurir, dan pembayaran pesanan customer.',
                'recipient_type' => 'CUSTOMER',
                'push_enabled' => true,
                'email_enabled' => true,
                'whatsapp_enabled' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ADMIN_ALERT',
                'name' => 'Peringatan Admin & Stok',
                'description' => 'Peringatan stok menipis, pesanan baru masuk, dan aktivitas sistem admin.',
                'recipient_type' => 'ADMIN',
                'push_enabled' => true,
                'email_enabled' => true,
                'whatsapp_enabled' => false,
                'is_active' => true,
            ],
        ];

        foreach ($settings as $settingData) {
            NotificationSetting::firstOrCreate(
                ['code' => $settingData['code']],
                $settingData
            );
        }

        $admin = User::first();
        $users = User::all();

        if ($users->isNotEmpty()) {
            $generalSetting = NotificationSetting::where('code', 'GENERAL_ANNOUNCEMENT')->first();
            $promoSetting = NotificationSetting::where('code', 'PROMO_DISCOUNT')->first();

            if (! Notification::where('title', 'Selamat Datang di Toko Batik Nusantara')->exists()) {
                $notif1 = Notification::create([
                    'notification_setting_id' => $generalSetting?->id,
                    'title' => 'Selamat Datang di Toko Batik Nusantara',
                    'message' => 'Terima kasih telah bergabung dengan platform e-commerce batik kami. Temukan koleksi batik tulis, cap, dan printing berkualitas tinggi.',
                    'recipient_type' => 'ALL_USERS',
                    'created_by' => $admin?->id,
                    'created_at' => now()->subDays(2),
                ]);

                foreach ($users as $user) {
                    NotificationRecipient::firstOrCreate([
                        'notification_id' => $notif1->id,
                        'user_id' => $user->id,
                    ], [
                        'is_read' => true,
                        'read_at' => now()->subDay(),
                    ]);
                }
            }

            if (! Notification::where('title', 'Flash Sale Batik Pekalongan Diskon 25%')->exists()) {
                $notif2 = Notification::create([
                    'notification_setting_id' => $promoSetting?->id,
                    'title' => 'Flash Sale Batik Pekalongan Diskon 25%',
                    'message' => 'Gunakan kode voucher BATIK25 saat checkout untuk mendapatkan potongan harga hingga Rp 100.000 berlaku minggu ini.',
                    'recipient_type' => 'CUSTOMER',
                    'created_by' => $admin?->id,
                    'created_at' => now()->subHours(5),
                ]);

                foreach ($users as $index => $user) {
                    NotificationRecipient::firstOrCreate([
                        'notification_id' => $notif2->id,
                        'user_id' => $user->id,
                    ], [
                        'is_read' => $index === 0,
                        'read_at' => $index === 0 ? now()->subHours(2) : null,
                    ]);
                }
            }
        }
    }
}
