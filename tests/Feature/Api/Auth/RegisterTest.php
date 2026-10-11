<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Siti Aminah',
            'username' => 'siti_aminah',
            'email' => 'siti@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_customer_can_register_and_receives_token(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('meta.code', 201)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('meta.message', 'Registrasi berhasil.')
            ->assertJsonPath('data.user.username', 'siti_aminah')
            ->assertJsonPath('data.user.email', 'siti@example.com')
            ->assertJsonPath('data.user.phone', '081234567890')
            ->assertJsonPath('data.user.roles', ['customer'])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    'user' => ['id', 'name', 'username', 'email', 'phone', 'roles', 'created_at'],
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJsonMissingPath('data.user.password');

        $user = User::where('email', 'siti@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->customer);
        $this->assertSame('081234567890', $user->customer->phone);
        $this->assertTrue($user->customer->is_active);
        $this->assertCount(1, $user->tokens);
    }

    public function test_customer_role_is_created_when_it_does_not_exist(): void
    {
        $this->assertDatabaseMissing('roles', ['name' => 'customer']);

        $this->postJson(route('api.v1.auth.register'), $this->validPayload())->assertCreated();

        $this->assertDatabaseHas('roles', ['name' => 'customer', 'guard_name' => 'api']);
        $this->assertDatabaseMissing('roles', ['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_existing_customer_role_is_reused(): void
    {
        $existingRole = Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->postJson(route('api.v1.auth.register'), $this->validPayload())->assertCreated();

        $this->assertSame(1, Role::where('name', 'customer')->count());
        $this->assertTrue(User::firstWhere('email', 'siti@example.com')->hasRole($existingRole));
    }

    public function test_user_can_still_be_assigned_web_guard_roles(): void
    {
        Role::create(['name' => 'super admin', 'guard_name' => 'web']);
        $user = User::factory()->create();

        $user->assignRole('super admin');

        $this->assertTrue($user->hasRole('super admin', 'web'));
    }

    public function test_phone_is_optional(): void
    {
        $payload = $this->validPayload();
        unset($payload['phone']);

        $this->postJson(route('api.v1.auth.register'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.user.phone', null);
    }

    public function test_validation_errors_use_formatted_response(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), []);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.code', 422)
            ->assertJsonPath('meta.status', 'error')
            ->assertJsonPath('data', null)
            ->assertJsonValidationErrors(['name', 'username', 'email', 'password']);
    }

    public function test_cannot_register_with_duplicate_email_or_username(): void
    {
        User::factory()->create(['email' => 'siti@example.com', 'username' => 'siti_aminah']);

        $this->postJson(route('api.v1.auth.register'), $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'username']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson(route('api.v1.auth.register'), $this->validPayload([
            'password_confirmation' => 'different-password',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }
}
