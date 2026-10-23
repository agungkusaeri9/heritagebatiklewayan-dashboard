<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_delete_account(): void
    {
        $response = $this->deleteJson('/api/v1/user/account');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'phone' => '081234567890',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/user/account');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Akun Anda berhasil dihapus.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_deleted' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_deleted' => true,
            'is_active' => false,
        ]);
    }

    public function test_deleted_account_cannot_login(): void
    {
        Role::findOrCreate('customer', 'api');

        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'password' => bcrypt('password123'),
            'is_deleted' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'phone' => '081234567890',
            'is_active' => false,
            'is_deleted' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'deleted@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }
}
