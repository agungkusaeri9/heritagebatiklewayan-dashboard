<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_or_update_profile(): void
    {
        $getResponse = $this->getJson('/api/v1/user/profile');
        $getResponse->assertStatus(401);

        $putResponse = $this->putJson('/api/v1/user/profile', [
            'name' => 'Agung Kusaeri',
            'phone' => '081234567890',
            'gender' => 'male',
        ]);
        $putResponse->assertStatus(401);
    }

    public function test_get_profile_returns_is_profile_complete_false_when_data_missing(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.is_profile_complete', false);
    }

    public function test_update_profile_completes_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
        ]);
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Budi Santoso Updated',
            'phone' => '+6281234567890',
            'gender' => 'male',
        ];

        $response = $this->putJson('/api/v1/user/profile', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso Updated')
            ->assertJsonPath('data.phone', '+6281234567890')
            ->assertJsonPath('data.gender', 'male')
            ->assertJsonPath('data.is_profile_complete', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Budi Santoso Updated',
        ]);

        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'phone' => '+6281234567890',
            'gender' => 'male',
        ]);
    }

    public function test_update_profile_validation_errors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user/profile', [
            'name' => '',
            'phone' => '',
            'gender' => 'invalid-gender',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'gender']);
    }

    public function test_update_profile_with_avatar_image_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
        ]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $response = $this->postJson('/api/v1/user/profile', [
            'name' => 'Budi Santoso Dengan Foto',
            'phone' => '+6281234567890',
            'gender' => 'male',
            'avatar' => $file,
            '_method' => 'PUT',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso Dengan Foto')
            ->assertJsonPath('data.is_profile_complete', true);

        $customer = $user->fresh()->customer;
        $this->assertNotNull($customer->avatar);
        $this->assertStringContainsString('/storage/avatars/', $customer->avatar);

        $storedPath = str_replace('/storage/', '', $customer->avatar);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_update_profile_can_reset_avatar(): void
    {
        $user = User::factory()->create();
        $user->customer()->create([
            'phone' => '+6281234567890',
            'gender' => 'male',
            'avatar' => '/storage/avatars/old.jpg',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user/profile', [
            'name' => 'Budi Tanpa Foto',
            'phone' => '+6281234567890',
            'gender' => 'male',
            'avatar' => '',
        ]);

        $response->assertStatus(200);
        $this->assertNull($user->fresh()->customer->avatar);
    }
}
