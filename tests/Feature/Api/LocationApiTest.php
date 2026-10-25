<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Province::create(['province_id' => 10, 'province_name' => 'Jawa Tengah']);
        Province::create(['province_id' => 11, 'province_name' => 'Jawa Timur']);

        City::create(['city_id' => 444, 'province_id' => 11, 'city_name' => 'Kota Surabaya', 'postal_code' => '60119']);
        City::create(['city_id' => 445, 'province_id' => 10, 'city_name' => 'Kota Surakarta (Solo)', 'postal_code' => '57113']);

        Subdistrict::create(['subdistrict_id' => 6164, 'city_id' => 445, 'subdistrict_name' => 'Laweyan']);
        Subdistrict::create(['subdistrict_id' => 6162, 'city_id' => 445, 'subdistrict_name' => 'Banjarsari']);
    }

    public function test_can_get_provinces(): void
    {
        $response = $this->getJson(route('api.v1.locations.provinces'));

        $response->assertOk()
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_cities_filtered_by_province(): void
    {
        $response = $this->getJson(route('api.v1.locations.cities', ['province_id' => 10]));

        $response->assertOk()
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kota Surakarta (Solo)');
    }

    public function test_can_get_subdistricts_filtered_by_city(): void
    {
        $response = $this->getJson(route('api.v1.locations.subdistricts', ['city_id' => 445]));

        $response->assertOk()
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonCount(2, 'data');
    }
}
