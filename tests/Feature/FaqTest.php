<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_cannot_access_faqs(): void
    {
        $response = $this->get(route('faqs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_faqs_list(): void
    {
        $user = User::factory()->create();
        Faq::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('faqs.index'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_faq(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('faqs.store'), [
            'title' => 'Apakah tersedia motif batik tulis?',
            'description' => 'Ya, kami memiliki koleksi batik tulis asli.',
            'category' => 'Produk Batik',
            'order' => 1,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', [
            'title' => 'Apakah tersedia motif batik tulis?',
            'category' => 'Produk Batik',
        ]);
    }

    public function test_authenticated_users_can_update_faq(): void
    {
        $user = User::factory()->create();
        $faq = Faq::factory()->create([
            'title' => 'Judul Lama',
            'description' => 'Deskripsi Lama',
            'category' => 'Pemesanan',
        ]);

        $response = $this->actingAs($user)->put(route('faqs.update', $faq), [
            'title' => 'Judul Baru',
            'description' => 'Deskripsi Baru',
            'category' => 'Pemesanan & Checkout',
            'order' => 2,
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'title' => 'Judul Baru',
            'category' => 'Pemesanan & Checkout',
            'is_active' => false,
        ]);
    }

    public function test_authenticated_users_can_delete_faq(): void
    {
        $user = User::factory()->create();
        $faq = Faq::factory()->create();

        $response = $this->actingAs($user)->delete(route('faqs.destroy', $faq));

        $response->assertRedirect();
        $this->assertDatabaseMissing('faqs', [
            'id' => $faq->id,
        ]);
    }

    public function test_authenticated_users_can_toggle_faq_status(): void
    {
        $user = User::factory()->create();
        $faq = Faq::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('faqs.toggle', $faq));

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'is_active' => false,
        ]);
    }
}
