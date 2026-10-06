<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_notifications_list(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_notification_for_all_users(): void
    {
        $admin = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $setting = NotificationSetting::factory()->create();

        $response = $this->actingAs($admin)->post(route('notifications.store'), [
            'notification_setting_id' => $setting->id,
            'title' => 'Pengumuman Penting Toko',
            'message' => 'Toko kami akan melakukan pembaruan sistem malam ini.',
            'recipient_type' => 'ALL_USERS',
        ]);

        $response->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'title' => 'Pengumuman Penting Toko',
            'recipient_type' => 'ALL_USERS',
            'created_by' => $admin->id,
        ]);

        $notification = Notification::where('title', 'Pengumuman Penting Toko')->first();
        $this->assertNotNull($notification);

        // All 3 users should have a recipient row
        $this->assertEquals(3, NotificationRecipient::where('notification_id', $notification->id)->count());
    }

    public function test_authenticated_users_can_create_notification_for_specific_users(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('notifications.store'), [
            'title' => 'Pemberitahuan Khusus',
            'message' => 'Pesan khusus hanya untuk Anda.',
            'recipient_type' => 'SPECIFIC_USER',
            'user_ids' => [$targetUser->id],
        ]);

        $response->assertRedirect(route('notifications.index'));

        $notification = Notification::where('title', 'Pemberitahuan Khusus')->first();
        $this->assertNotNull($notification);

        $this->assertEquals(1, NotificationRecipient::where('notification_id', $notification->id)->count());
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $targetUser->id,
            'is_read' => false,
        ]);
        $this->assertDatabaseMissing('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_authenticated_users_can_view_notification_details(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['created_by' => $user->id]);
        NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get(route('notifications.show', $notification));

        $response->assertOk();
    }

    public function test_authenticated_users_can_mark_all_recipients_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();
        $recipient1 = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->post(route('notifications.mark-all-read', $notification));

        $response->assertRedirect();
        $this->assertTrue($recipient1->fresh()->is_read);
        $this->assertNotNull($recipient1->fresh()->read_at);
    }

    public function test_authenticated_users_can_mark_notification_as_read_for_themselves(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();
        $recipient = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->post(route('notifications.mark-read', $notification));

        $response->assertRedirect();
        $this->assertTrue($recipient->fresh()->is_read);
    }

    public function test_authenticated_users_can_delete_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->delete(route('notifications.destroy', $notification));

        $response->assertRedirect(route('notifications.index'));
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_notification_settings_crud(): void
    {
        $user = User::factory()->create();

        // Create setting
        $response = $this->actingAs($user)->post(route('notification-settings.store'), [
            'code' => 'NEW_PROMO_CODE',
            'name' => 'Promo Diskon Baru',
            'description' => 'Untuk event bulanan',
            'recipient_type' => 'CUSTOMER',
            'push_enabled' => true,
            'email_enabled' => true,
            'whatsapp_enabled' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $this->assertDatabaseHas('notification_settings', [
            'code' => 'NEW_PROMO_CODE',
            'name' => 'Promo Diskon Baru',
        ]);

        $setting = NotificationSetting::where('code', 'NEW_PROMO_CODE')->first();

        // Update setting
        $response = $this->actingAs($user)->put(route('notification-settings.update', $setting), [
            'code' => 'NEW_PROMO_CODE_UPDATED',
            'name' => 'Promo Diskon Diperbarui',
            'recipient_type' => 'ALL_USERS',
            'push_enabled' => true,
            'email_enabled' => false,
            'whatsapp_enabled' => false,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $this->assertDatabaseHas('notification_settings', [
            'id' => $setting->id,
            'code' => 'NEW_PROMO_CODE_UPDATED',
            'is_active' => false,
        ]);

        // Delete setting
        $response = $this->actingAs($user)->delete(route('notification-settings.destroy', $setting));
        $response->assertRedirect(route('notification-settings.index'));
        $this->assertDatabaseMissing('notification_settings', [
            'id' => $setting->id,
        ]);
    }
}
