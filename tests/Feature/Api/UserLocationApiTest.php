<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_location(): void
    {
        $response = $this->getJson('/api/v1/user/location');
        $response->assertStatus(401);

        $putResponse = $this->putJson('/api/v1/user/location', [
            'source' => 'manual',
            'address' => 'Solo, Jawa Tengah',
        ]);
        $putResponse->assertStatus(401);
    }

    public function test_get_location_when_not_set_returns_null_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user/location');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'status' => 'success',
                ],
                'data' => null,
            ]);
    }

    public function test_update_location_with_gps_coordinates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'source' => 'gps',
            'latitude' => -7.5682123,
            'longitude' => 110.8251234,
            'address' => 'Laweyan, Surakarta',
            'label' => 'Lokasi Saat Ini',
        ];

        $response = $this->putJson('/api/v1/user/location', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.source', 'gps')
            ->assertJsonPath('data.latitude', -7.5682123)
            ->assertJsonPath('data.longitude', 110.8251234)
            ->assertJsonPath('data.address', 'Laweyan, Surakarta')
            ->assertJsonPath('data.label', 'Lokasi Saat Ini');

        $this->assertDatabaseHas('user_locations', [
            'user_id' => $user->id,
            'source' => 'gps',
            'address' => 'Laweyan, Surakarta',
        ]);
    }

    public function test_update_location_with_manual_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'source' => 'manual',
            'address' => 'Jl. Dr. Rajiman No. 521, Laweyan, Surakarta',
            'label' => 'Galeri Batik Laweyan',
        ];

        $response = $this->putJson('/api/v1/user/location', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.source', 'manual')
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null)
            ->assertJsonPath('data.address', 'Jl. Dr. Rajiman No. 521, Laweyan, Surakarta')
            ->assertJsonPath('data.label', 'Galeri Batik Laweyan');
    }

    public function test_get_location_returns_previously_saved_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        UserLocation::create([
            'user_id' => $user->id,
            'source' => 'gps',
            'latitude' => -7.5500,
            'longitude' => 110.8000,
            'address' => 'Kampung Batik Laweyan',
            'label' => 'Rumah',
        ]);

        $response = $this->getJson('/api/v1/user/location');

        $response->assertStatus(200)
            ->assertJsonPath('data.source', 'gps')
            ->assertJsonPath('data.address', 'Kampung Batik Laweyan');
    }
}
