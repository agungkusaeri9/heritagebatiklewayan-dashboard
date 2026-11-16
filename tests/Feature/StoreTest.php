<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_stores()
    {
        $response = $this->get(route('stores.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_role_permission_seeder_creates_admin_store_role()
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'admin store')->where('guard_name', 'web')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('view stores'));
        $this->assertTrue($role->hasPermissionTo('edit stores'));
        $this->assertTrue($role->hasPermissionTo('view products'));
    }

    public function test_authenticated_users_can_view_stores_list()
    {
        $user = User::factory()->create();
        Store::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('stores.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('stores/Index')
            ->has('stores.data', 3)
            ->has('filters')
        );
    }

    public function test_creating_store_also_creates_admin_store_user_and_uploads_logo()
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $logo = UploadedFile::fake()->image('store-logo.png');

        $response = $this->actingAs($admin)->post(route('stores.store'), [
            'name' => 'Batik Laweyan Jaya',
            'email' => '', // email opsional
            'phone' => '0271712345',
            'whatsapp' => '6281234567890',
            'logo' => $logo,
            'description' => 'Toko batik tulis heritage asli Solo.',
            'seller_name' => 'Seller Jaya',
            'seller_username' => 'sellerjaya',
            'seller_email' => '',
            'seller_password' => 'password123',
        ]);

        $response->assertRedirect(route('stores.index'));

        $seller = User::where('username', 'sellerjaya')->first();
        $this->assertNotNull($seller);
        $this->assertTrue($seller->hasRole('admin store', 'web'));

        $this->assertDatabaseHas('stores', [
            'user_id' => $seller->id,
            'name' => 'Batik Laweyan Jaya',
            'email' => null,
            'phone' => '0271712345',
            'whatsapp' => '6281234567890',
        ]);

        $store = Store::where('name', 'Batik Laweyan Jaya')->first();
        $this->assertNotNull($store->logo);
        Storage::disk('public')->assertExists($store->logo);
    }

    public function test_editing_store_can_update_existing_seller_or_create_new_admin_store_user()
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $oldSeller = User::factory()->create([
            'name' => 'Old Seller',
            'username' => 'oldseller',
        ]);
        $store = Store::factory()->create([
            'user_id' => $oldSeller->id,
            'name' => 'Toko Awal',
        ]);

        // 1. Update store & current seller
        $response = $this->actingAs($admin)->put(route('stores.update', $store), [
            'name' => 'Toko Update',
            'email' => 'toko@example.com',
            'phone' => '0271000111',
            'whatsapp' => '628111222333',
            'description' => 'Deskripsi terbaru',
            'account_mode' => 'update',
            'seller_name' => 'Updated Seller Name',
            'seller_username' => 'oldseller',
            'seller_email' => 'updatedseller@example.com',
        ]);

        $response->assertRedirect(route('stores.index'));
        $this->assertDatabaseHas('users', [
            'id' => $oldSeller->id,
            'name' => 'Updated Seller Name',
            'email' => 'updatedseller@example.com',
        ]);
        $this->assertTrue($oldSeller->fresh()->hasRole('admin store', 'web'));

        // 2. Directly create a new Admin Store seller when editing the store
        $responseCreateNew = $this->actingAs($admin)->put(route('stores.update', $store), [
            'name' => 'Toko Update Baru',
            'email' => 'tokobaru@example.com',
            'phone' => '0271000222',
            'whatsapp' => '628111222444',
            'description' => 'Ganti seller baru langsung saat edit',
            'account_mode' => 'create',
            'seller_name' => 'Seller Pengganti',
            'seller_username' => 'sellerpengganti',
            'seller_email' => 'pengganti@example.com',
            'seller_password' => 'password123',
        ]);

        $responseCreateNew->assertRedirect(route('stores.index'));

        $newSeller = User::where('username', 'sellerpengganti')->first();
        $this->assertNotNull($newSeller);
        $this->assertTrue($newSeller->hasRole('admin store', 'web'));
        $this->assertSame($newSeller->id, $store->fresh()->user_id);
    }

    public function test_admin_store_user_can_only_access_and_edit_their_own_store()
    {
        $this->seed(RolePermissionSeeder::class);

        $sellerA = User::factory()->create();
        $sellerA->assignRole('admin store');
        $storeA = Store::factory()->create(['user_id' => $sellerA->id, 'name' => 'Toko A']);

        $sellerB = User::factory()->create();
        $sellerB->assignRole('admin store');
        $storeB = Store::factory()->create(['user_id' => $sellerB->id, 'name' => 'Toko B']);

        $indexResponse = $this->actingAs($sellerA)->get(route('stores.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page
            ->component('stores/Index')
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Toko A')
        );

        $this->actingAs($sellerA)->get(route('stores.edit', $storeA))->assertOk();
        $this->actingAs($sellerA)->get(route('stores.edit', $storeB))->assertForbidden();
        $this->actingAs($sellerA)->get(route('stores.create'))->assertForbidden();
        $this->actingAs($sellerA)->delete(route('stores.destroy', $storeA))->assertForbidden();
    }

    public function test_authenticated_users_can_delete_store()
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');
        $path = $logo->store('stores', 'public');

        $store = Store::factory()->create([
            'logo' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($admin)->delete(route('stores.destroy', $store));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
