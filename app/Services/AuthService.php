<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public const CUSTOMER_ROLE = 'customer';

    public const API_GUARD = 'api';

    /**
     * Register a new customer account and issue an API token.
     *
     * @param  array{name: string, username: string, email: string, password: string, phone?: string|null}  $data
     * @return array{user: User, token: string}
     */
    public function registerCustomer(array $data, string $deviceName = 'api'): array
    {
        return DB::transaction(function () use ($data, $deviceName) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->customer()->create([
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            $customerRole = Role::findOrCreate(self::CUSTOMER_ROLE, self::API_GUARD);
            $user->assignRole($customerRole);

            $token = $user->createToken($deviceName)->plainTextToken;

            return [
                'user' => $user->load(['customer', 'roles']),
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate a customer by email and password and issue an API token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     * @throws AccessDeniedHttpException
     */
    public function loginCustomer(array $credentials, string $deviceName = 'api'): array
    {
        $sanitizedCredentials = $credentials;
        if (isset($sanitizedCredentials['password'])) {
            $sanitizedCredentials['password'] = '******';
        }

        $user = User::with('customer')->firstWhere('email', $credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            Log::warning('Login gagal: Kredensial tidak valid.', ['request' => $sanitizedCredentials]);
            throw new AuthenticationException('Email atau kata sandi salah.');
        }

        if ($user->is_deleted) {
            Log::warning('Login ditolak: Akun telah dihapus.', ['request' => $sanitizedCredentials, 'user_id' => $user->id]);
            throw new AccessDeniedHttpException('Akun ini telah dinonaktifkan atau dihapus.');
        }

        if (! $user->hasRole(self::CUSTOMER_ROLE, self::API_GUARD)) {
            Log::warning('Login ditolak: Bukan role customer.', ['request' => $sanitizedCredentials, 'user_id' => $user->id]);
            throw new AccessDeniedHttpException('Akun ini tidak memiliki akses sebagai customer.');
        }

        if (! $user->customer?->is_active || $user->customer?->is_deleted) {
            Log::warning('Login ditolak: Akun customer tidak aktif.', ['request' => $sanitizedCredentials, 'user_id' => $user->id]);
            throw new AccessDeniedHttpException('Akun customer tidak aktif.');
        }

        // Update fcm_token jika dikirim saat login
        if (! empty($credentials['fcm_token'])) {
            $user->update(['fcm_token' => $credentials['fcm_token']]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        Log::info('Login berhasil.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'request' => $sanitizedCredentials,
        ]);

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    /**
     * Refresh personal access token: revoke the current token and issue a new one.
     */
    public function refreshToken(User $user, string $deviceName = 'api'): string
    {
        $user->currentAccessToken()?->delete();

        return $user->createToken($deviceName)->plainTextToken;
    }
}
