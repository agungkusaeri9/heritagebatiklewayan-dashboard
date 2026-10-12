<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Province::create(['province_id' => 1, 'province_name' => 'Jawa Barat']);
        Province::create(['province_id' => 2, 'province_name' => 'Jawa Tengah']);
        City::create(['city_id' => 10, 'province_id' => 1, 'city_name' => 'Bandung', 'postal_code' => '40111']);
        City::create(['city_id' => 20, 'province_id' => 2, 'city_name' => 'Pekalongan', 'postal_code' => '51111']);
        Subdistrict::create(['subdistrict_id' => 100, 'city_id' => 10, 'subdistrict_name' => 'Coblong']);
        Subdistrict::create(['subdistrict_id' => 200, 'city_id' => 20, 'subdistrict_name' => 'Pekalongan Barat']);

        $this->customer = Customer::factory()->create();
        $this->user = $this->customer->user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Rumah',
            'recipient_name' => 'Siti Aminah',
            'phone' => '081234567890',
            'full_address' => 'Jl. Dago No. 1',
            'province_id' => 1,
            'city_id' => 10,
            'subdistrict_id' => 100,
            'postal_code' => '40135',
        ], $overrides);
    }

    public function test_guest_cannot_access_addresses(): void
    {
        $this->getJson(route('api.v1.addresses.index'))
            ->assertUnauthorized()
            ->assertJsonPath('meta.status', 'error');
    }

    public function test_user_without_customer_profile_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.addresses.index'))->assertForbidden();
    }

    public function test_user_can_list_only_own_addresses_with_primary_first(): void
    {
        CustomerAddress::factory()->for($this->customer)->create(['label' => 'Kantor']);
        CustomerAddress::factory()->for($this->customer)->primary()->create(['label' => 'Rumah']);
        CustomerAddress::factory()->create(['label' => 'Orang Lain']);

        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.addresses.index'))
            ->assertOk()
            ->assertJsonPath('meta.message', 'Berhasil mengambil daftar alamat.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label', 'Rumah')
            ->assertJsonPath('data.0.is_primary', true)
            ->assertJsonPath('data.1.label', 'Kantor');
    }

    public function test_first_address_is_automatically_primary(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.addresses.store'), $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('meta.code', 201)
            ->assertJsonPath('data.recipient_name', 'Siti Aminah')
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.province.name', 'Jawa Barat')
            ->assertJsonPath('data.city.name', 'Bandung')
            ->assertJsonPath('data.subdistrict.name', 'Coblong');

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'recipient_name' => 'Siti Aminah',
            'is_primary' => true,
        ]);
    }

    public function test_label_defaults_to_rumah(): void
    {
        Sanctum::actingAs($this->user);

        $payload = $this->validPayload();
        unset($payload['label']);

        $this->postJson(route('api.v1.addresses.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.label', 'Rumah');
    }

    public function test_storing_primary_address_unsets_previous_primary(): void
    {
        $oldPrimary = CustomerAddress::factory()->for($this->customer)->primary()->create();

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.addresses.store'), $this->validPayload(['is_primary' => true]))
            ->assertCreated()
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse($oldPrimary->fresh()->is_primary);
        $this->assertSame(1, $this->customer->addresses()->where('is_primary', true)->count());
    }

    public function test_storing_non_primary_address_keeps_existing_primary(): void
    {
        $primary = CustomerAddress::factory()->for($this->customer)->primary()->create();

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.addresses.store'), $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.is_primary', false);

        $this->assertTrue($primary->fresh()->is_primary);
    }

    public function test_store_validation_errors(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.addresses.store'), [])
            ->assertUnprocessable()
            ->assertJsonPath('meta.status', 'error')
            ->assertJsonValidationErrors([
                'recipient_name', 'phone', 'full_address', 'province_id', 'city_id', 'subdistrict_id',
            ]);
    }

    public function test_store_rejects_city_and_subdistrict_outside_hierarchy(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.addresses.store'), $this->validPayload([
            'province_id' => 1,
            'city_id' => 20,
            'subdistrict_id' => 200,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id'])
            ->assertJsonMissingValidationErrors(['subdistrict_id']);

        $this->postJson(route('api.v1.addresses.store'), $this->validPayload([
            'province_id' => 1,
            'city_id' => 10,
            'subdistrict_id' => 200,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subdistrict_id'])
            ->assertJsonMissingValidationErrors(['city_id']);

        $this->assertDatabaseCount('customer_addresses', 0);
    }

    public function test_user_can_show_own_address(): void
    {
        $address = CustomerAddress::factory()->for($this->customer)->create([
            'province_id' => 1, 'city_id' => 10, 'subdistrict_id' => 100,
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.addresses.show', $address->id))
            ->assertOk()
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.city.id', 10)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    'id', 'label', 'recipient_name', 'phone', 'full_address',
                    'province', 'city', 'subdistrict', 'postal_code', 'is_primary',
                    'created_at', 'updated_at',
                ],
            ]);
    }

    public function test_user_cannot_access_other_users_address(): void
    {
        $otherAddress = CustomerAddress::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.addresses.show', $otherAddress->id))
            ->assertNotFound()
            ->assertJsonPath('meta.message', 'Alamat tidak ditemukan.');

        $this->putJson(route('api.v1.addresses.update', $otherAddress->id), ['label' => 'Hack'])
            ->assertNotFound();

        $this->patchJson(route('api.v1.addresses.set-primary', $otherAddress->id))
            ->assertNotFound();

        $this->deleteJson(route('api.v1.addresses.destroy', $otherAddress->id))
            ->assertNotFound();

        $this->assertDatabaseHas('customer_addresses', ['id' => $otherAddress->id, 'label' => $otherAddress->label]);
    }

    public function test_user_can_partially_update_address(): void
    {
        $address = CustomerAddress::factory()->for($this->customer)->create(['label' => 'Rumah']);

        Sanctum::actingAs($this->user);

        $this->patchJson(route('api.v1.addresses.update', $address->id), ['label' => 'Kantor'])
            ->assertOk()
            ->assertJsonPath('meta.message', 'Alamat berhasil diperbarui.')
            ->assertJsonPath('data.label', 'Kantor')
            ->assertJsonPath('data.recipient_name', $address->recipient_name);
    }

    public function test_update_location_requires_complete_hierarchy(): void
    {
        $address = CustomerAddress::factory()->for($this->customer)->create();

        Sanctum::actingAs($this->user);

        $this->patchJson(route('api.v1.addresses.update', $address->id), ['city_id' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_id', 'subdistrict_id']);

        $this->patchJson(route('api.v1.addresses.update', $address->id), [
            'province_id' => 2, 'city_id' => 20, 'subdistrict_id' => 200,
        ])
            ->assertOk()
            ->assertJsonPath('data.province.name', 'Jawa Tengah')
            ->assertJsonPath('data.subdistrict.name', 'Pekalongan Barat');
    }

    public function test_updating_to_primary_unsets_previous_primary(): void
    {
        $oldPrimary = CustomerAddress::factory()->for($this->customer)->primary()->create();
        $address = CustomerAddress::factory()->for($this->customer)->create();

        Sanctum::actingAs($this->user);

        $this->putJson(route('api.v1.addresses.update', $address->id), ['is_primary' => true])
            ->assertOk()
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse($oldPrimary->fresh()->is_primary);
    }

    public function test_primary_address_cannot_be_unset_directly(): void
    {
        $primary = CustomerAddress::factory()->for($this->customer)->primary()->create();

        Sanctum::actingAs($this->user);

        $this->patchJson(route('api.v1.addresses.update', $primary->id), ['is_primary' => false])
            ->assertOk()
            ->assertJsonPath('data.is_primary', true);
    }

    public function test_user_can_set_primary_address(): void
    {
        $oldPrimary = CustomerAddress::factory()->for($this->customer)->primary()->create();
        $address = CustomerAddress::factory()->for($this->customer)->create();

        Sanctum::actingAs($this->user);

        $this->patchJson(route('api.v1.addresses.set-primary', $address->id))
            ->assertOk()
            ->assertJsonPath('meta.message', 'Alamat utama berhasil diubah.')
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse($oldPrimary->fresh()->is_primary);
        $this->assertTrue($address->fresh()->is_primary);
    }

    public function test_user_can_delete_address(): void
    {
        $address = CustomerAddress::factory()->for($this->customer)->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson(route('api.v1.addresses.destroy', $address->id))
            ->assertOk()
            ->assertJsonPath('meta.message', 'Alamat berhasil dihapus.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    public function test_deleting_primary_address_promotes_latest_remaining_address(): void
    {
        $primary = CustomerAddress::factory()->for($this->customer)->primary()->create();
        $older = CustomerAddress::factory()->for($this->customer)->create();
        $latest = CustomerAddress::factory()->for($this->customer)->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson(route('api.v1.addresses.destroy', $primary->id))->assertOk();

        $this->assertTrue($latest->fresh()->is_primary);
        $this->assertFalse($older->fresh()->is_primary);
    }

    public function test_show_returns_not_found_for_missing_address(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.addresses.show', 999))->assertNotFound();
    }
}
