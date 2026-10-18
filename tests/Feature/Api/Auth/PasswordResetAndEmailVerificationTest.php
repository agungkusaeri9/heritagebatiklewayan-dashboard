<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use App\Notifications\Auth\AuthOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetAndEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_send_password_reset_otp_for_unregistered_email(): void
    {
        $response = $this->postJson(route('api.v1.auth.forgot-password.send-otp'), [
            'email' => 'unknown@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.code', 422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_send_password_reset_otp_for_registered_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $response = $this->postJson(route('api.v1.auth.forgot-password.send-otp'), [
            'email' => 'customer@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('data.email', 'customer@example.com');

        $this->assertTrue(Cache::has('auth_otp:password_reset:customer@example.com'));

        Notification::assertSentOnDemand(
            AuthOtpNotification::class,
            function (AuthOtpNotification $notification, array $channels, object $notifiable) {
                return $notifiable->routes['mail'] === 'customer@example.com'
                    && $notification->purpose === 'reset_password';
            }
        );
    }

    public function test_cannot_send_password_reset_otp_during_cooldown(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'customer@example.com']);

        // First request
        $this->postJson(route('api.v1.auth.forgot-password.send-otp'), [
            'email' => 'customer@example.com',
        ])->assertOk();

        // Second immediate request
        $response = $this->postJson(route('api.v1.auth.forgot-password.send-otp'), [
            'email' => 'customer@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_verify_password_reset_otp_with_invalid_code(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        Cache::put('auth_otp:password_reset:customer@example.com', [
            'otp' => '123456',
            'attempts' => 0,
        ], now()->addMinutes(5));

        $response = $this->postJson(route('api.v1.auth.forgot-password.verify-otp'), [
            'email' => 'customer@example.com',
            'otp' => '654321',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_can_verify_password_reset_otp_and_receives_reset_token(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        Cache::put('auth_otp:password_reset:customer@example.com', [
            'otp' => '123456',
            'attempts' => 0,
        ], now()->addMinutes(5));

        $response = $this->postJson(route('api.v1.auth.forgot-password.verify-otp'), [
            'email' => 'customer@example.com',
            'otp' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('data.email', 'customer@example.com')
            ->assertJsonStructure([
                'data' => [
                    'email',
                    'reset_token',
                ],
            ]);

        $resetToken = $response->json('data.reset_token');
        $this->assertNotEmpty($resetToken);
        $this->assertSame($resetToken, Cache::get('auth_reset_token:customer@example.com'));
        $this->assertFalse(Cache::has('auth_otp:password_reset:customer@example.com'));
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $plainToken = 'valid-test-reset-token-123';
        Cache::put('auth_reset_token:customer@example.com', $plainToken, now()->addMinutes(15));

        $response = $this->postJson(route('api.v1.auth.reset-password'), [
            'email' => 'customer@example.com',
            'reset_token' => $plainToken,
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-password-123', $user->password));
        $this->assertFalse(Cache::has('auth_reset_token:customer@example.com'));
    }

    public function test_cannot_reset_password_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $response = $this->postJson(route('api.v1.auth.reset-password'), [
            'email' => 'customer@example.com',
            'reset_token' => 'invalid-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reset_token']);
    }

    public function test_can_send_and_verify_email_verification_otp(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'verify@example.com',
        ]);

        $this->assertNull($user->email_verified_at);

        // Send OTP
        $sendResponse = $this->postJson(route('api.v1.auth.email.send-otp'), [
            'email' => 'verify@example.com',
        ]);

        $sendResponse->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('data.email', 'verify@example.com');

        $this->assertTrue(Cache::has('auth_otp:email_verify:verify@example.com'));

        // Manually retrieve OTP from cache
        $cachedOtp = Cache::get('auth_otp:email_verify:verify@example.com')['otp'];

        // Verify OTP
        $verifyResponse = $this->postJson(route('api.v1.auth.email.verify-otp'), [
            'email' => 'verify@example.com',
            'otp' => $cachedOtp,
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_cannot_send_email_verification_otp_if_already_verified(): void
    {
        User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.auth.email.send-otp'), [
            'email' => 'verified@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
