<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->user = $this->customer->user;
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson(route('api.v1.notifications.index'))
            ->assertUnauthorized()
            ->assertJsonPath('meta.status', 'error');
    }

    public function test_user_can_get_all_broadcast_notifications_and_direct_notifications(): void
    {
        $setting = NotificationSetting::create([
            'code' => 'PROMO',
            'name' => 'Promo Diskon',
            'push_enabled' => true,
            'is_active' => true,
        ]);

        // 1. Broadcast to ALL_USERS
        $notifAll = Notification::create([
            'notification_setting_id' => $setting->id,
            'title' => 'Promo Flash Sale Batik',
            'message' => 'Diskon hingga 50% untuk semua batik!',
            'recipient_type' => 'ALL_USERS',
        ]);

        // 2. Broadcast to CUSTOMER
        $notifCustomer = Notification::create([
            'title' => 'Voucher Eksklusif Member',
            'message' => 'Gunakan kode BATIKMEMBER untuk cashback.',
            'recipient_type' => 'CUSTOMER',
        ]);

        // 3. Direct to this specific user
        $notifDirect = Notification::create([
            'title' => 'Pesanan Anda Sedang Dikirim',
            'message' => 'Paket pesanan ORD-123 telah diserahkan ke kurir.',
            'recipient_type' => 'SPECIFIC_USER',
        ]);
        NotificationRecipient::create([
            'notification_id' => $notifDirect->id,
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        // 4. Other user direct notification (should NOT appear)
        $otherUser = User::factory()->create();
        $notifOther = Notification::create([
            'title' => 'Pesanan Orang Lain',
            'message' => 'Ini rahasia user lain.',
            'recipient_type' => 'SPECIFIC_USER',
        ]);
        NotificationRecipient::create([
            'notification_id' => $notifOther->id,
            'user_id' => $otherUser->id,
            'is_read' => false,
        ]);

        // 5. Broadcast ADMIN only (should NOT appear for customer)
        Notification::create([
            'title' => 'Laporan Admin Mingguan',
            'message' => 'Hanya untuk staf admin.',
            'recipient_type' => 'ADMIN',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.v1.notifications.index'));

        $response->assertOk()
            ->assertJsonPath('meta.message', 'Berhasil mengambil daftar notifikasi.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'Pesanan Anda Sedang Dikirim')
            ->assertJsonPath('data.0.user_id', $this->user->id)
            ->assertJsonPath('data.0.is_read', false)
            ->assertJsonPath('data.1.title', 'Voucher Eksklusif Member')
            ->assertJsonPath('data.1.user_id', $this->user->id)
            ->assertJsonPath('data.2.title', 'Promo Flash Sale Batik')
            ->assertJsonPath('data.2.user_id', $this->user->id)
            ->assertJsonPath('data.2.category.code', 'PROMO');
    }

    public function test_user_can_paginate_notifications(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Notification::create([
                'title' => "Notifikasi {$i}",
                'message' => "Pesan ke-{$i}",
                'recipient_type' => 'ALL_USERS',
            ]);
        }

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.v1.notifications.index', ['per_page' => 2]));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 5)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.last_page', 3);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $notif1 = Notification::create([
            'title' => 'Notif 1',
            'message' => 'Pesan 1',
            'recipient_type' => 'ALL_USERS',
        ]);

        $notif2 = Notification::create([
            'title' => 'Notif 2',
            'message' => 'Pesan 2',
            'recipient_type' => 'CUSTOMER',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.notifications.mark-all-read'));

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('data.updated_count', 2);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notif1->id,
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notif2->id,
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);
    }

    public function test_user_can_mark_subset_of_notifications_as_read(): void
    {
        $notif1 = Notification::create([
            'title' => 'Notif 1',
            'message' => 'Pesan 1',
            'recipient_type' => 'ALL_USERS',
        ]);

        $notif2 = Notification::create([
            'title' => 'Notif 2',
            'message' => 'Pesan 2',
            'recipient_type' => 'ALL_USERS',
        ]);

        Sanctum::actingAs($this->user);

        // Mark only notif1
        $response = $this->postJson(route('api.v1.notifications.mark-read'), [
            'notification_ids' => [$notif1->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('data.updated_count', 1)
            ->assertJsonPath('data.notification_ids', [$notif1->id]);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notif1->id,
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseMissing('notification_recipients', [
            'notification_id' => $notif2->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $notif = Notification::create([
            'title' => 'Notif Single',
            'message' => 'Pesan Single',
            'recipient_type' => 'ALL_USERS',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.notifications.mark-single-read', $notif->id));

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('data.notification_id', $notif->id)
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notif->id,
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);
    }
}
