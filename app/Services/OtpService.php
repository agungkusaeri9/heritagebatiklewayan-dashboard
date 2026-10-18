<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Auth\AuthOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const OTP_EXPIRY_MINUTES = 5;

    public const RESET_TOKEN_EXPIRY_MINUTES = 15;

    public const COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    /**
     * Send OTP for password reset.
     *
     * @throws ValidationException
     */
    public function sendPasswordResetOtp(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email tidak terdaftar dalam sistem.',
            ]);
        }

        if (Cache::has("auth_otp_cooldown:password_reset:{$email}")) {
            throw ValidationException::withMessages([
                'email' => 'Harap tunggu 1 menit sebelum meminta kode OTP pemulihan baru.',
            ]);
        }

        $otp = $this->generateOtp();

        Cache::put(
            "auth_otp:password_reset:{$email}",
            ['otp' => $otp, 'attempts' => 0],
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );

        Cache::put(
            "auth_otp_cooldown:password_reset:{$email}",
            true,
            now()->addSeconds(self::COOLDOWN_SECONDS)
        );

        Notification::route('mail', $email)->notify(
            new AuthOtpNotification($otp, 'reset_password', self::OTP_EXPIRY_MINUTES)
        );
    }

    /**
     * Verify OTP for password reset and issue a temporary reset token.
     *
     * @throws ValidationException
     */
    public function verifyPasswordResetOtp(string $email, string $otp): string
    {
        $cached = Cache::get("auth_otp:password_reset:{$email}");

        if (! $cached) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP telah kedaluwarsa atau belum diminta.',
            ]);
        }

        if ($cached['otp'] !== $otp) {
            $cached['attempts']++;

            if ($cached['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget("auth_otp:password_reset:{$email}");
                throw ValidationException::withMessages([
                    'otp' => 'Batas percobaan OTP telah terlampaui. Silakan minta kode baru.',
                ]);
            }

            Cache::put(
                "auth_otp:password_reset:{$email}",
                $cached,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid.',
            ]);
        }

        Cache::forget("auth_otp:password_reset:{$email}");

        $resetToken = Str::random(64);

        Cache::put(
            "auth_reset_token:{$email}",
            $resetToken,
            now()->addMinutes(self::RESET_TOKEN_EXPIRY_MINUTES)
        );

        return $resetToken;
    }

    /**
     * Reset user password using the temporary reset token.
     *
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $resetToken, string $newPassword): void
    {
        $cachedToken = Cache::get("auth_reset_token:{$email}");

        if (! $cachedToken || ! hash_equals($cachedToken, $resetToken)) {
            throw ValidationException::withMessages([
                'reset_token' => 'Token pemulihan kata sandi tidak valid atau telah kedaluwarsa.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna tidak ditemukan.',
            ]);
        }

        $user->forceFill([
            'password' => $newPassword,
        ])->save();

        Cache::forget("auth_reset_token:{$email}");

        // Revoke all existing tokens for safety
        $user->tokens()->delete();
    }

    /**
     * Send OTP for email verification.
     *
     * @throws ValidationException
     */
    public function sendEmailVerificationOtp(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email tidak terdaftar dalam sistem.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email ini sudah terverifikasi sebelumnya.',
            ]);
        }

        if (Cache::has("auth_otp_cooldown:email_verify:{$email}")) {
            throw ValidationException::withMessages([
                'email' => 'Harap tunggu 1 menit sebelum meminta kode OTP verifikasi baru.',
            ]);
        }

        $otp = $this->generateOtp();

        Cache::put(
            "auth_otp:email_verify:{$email}",
            ['otp' => $otp, 'attempts' => 0],
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );

        Cache::put(
            "auth_otp_cooldown:email_verify:{$email}",
            true,
            now()->addSeconds(self::COOLDOWN_SECONDS)
        );

        Notification::route('mail', $email)->notify(
            new AuthOtpNotification($otp, 'email_verification', self::OTP_EXPIRY_MINUTES)
        );
    }

    /**
     * Verify OTP and mark user's email as verified.
     *
     * @throws ValidationException
     */
    public function verifyEmailOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna tidak ditemukan.',
            ]);
        }

        $cached = Cache::get("auth_otp:email_verify:{$email}");

        if (! $cached) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP telah kedaluwarsa atau belum diminta.',
            ]);
        }

        if ($cached['otp'] !== $otp) {
            $cached['attempts']++;

            if ($cached['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget("auth_otp:email_verify:{$email}");
                throw ValidationException::withMessages([
                    'otp' => 'Batas percobaan OTP telah terlampaui. Silakan minta kode baru.',
                ]);
            }

            Cache::put(
                "auth_otp:email_verify:{$email}",
                $cached,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid.',
            ]);
        }

        Cache::forget("auth_otp:email_verify:{$email}");

        $user->markEmailAsVerified();

        return $user;
    }

    /**
     * Generate a 6-digit numeric OTP.
     */
    protected function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
