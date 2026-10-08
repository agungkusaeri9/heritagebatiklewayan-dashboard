<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_cannot_access_privacy_policy_settings(): void
    {
        $response = $this->get(route('privacy-policy.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_privacy_policy_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('privacy-policy.index'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_update_privacy_and_policy(): void
    {
        $user = User::factory()->create();
        $setting = StoreSetting::first() ?? StoreSetting::create([
            'name' => 'Toko Batik Test',
            'whatsapp' => '6281234567890',
        ]);
        $setting->update([
            'privacy_policy' => 'Kebijakan privasi lama',
            'policy' => 'Kebijakan toko lama',
        ]);

        $response = $this->actingAs($user)->post(route('privacy-policy.update'), [
            'privacy_policy' => 'Konten kebijakan privasi baru untuk perlindungan data batik customer.',
            'policy' => 'Syarat dan ketentuan pembelian serta pengembalian produk batik baru.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Kebijakan dan Privasi berhasil diperbarui.');

        $this->assertDatabaseHas('store_settings', [
            'id' => $setting->id,
            'privacy_policy' => 'Konten kebijakan privasi baru untuk perlindungan data batik customer.',
            'policy' => 'Syarat dan ketentuan pembelian serta pengembalian produk batik baru.',
        ]);
    }

    public function test_update_creates_store_setting_if_none_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('privacy-policy.update'), [
            'privacy_policy' => 'Kebijakan privasi awal.',
            'policy' => 'Kebijakan toko awal.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('store_settings', [
            'privacy_policy' => 'Kebijakan privasi awal.',
            'policy' => 'Kebijakan toko awal.',
        ]);
    }
}
