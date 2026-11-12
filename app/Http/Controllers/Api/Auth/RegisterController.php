<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private OtpService $otpService,
    ) {}

    /**
     * Register a new customer account.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerCustomer(
            $request->validated(),
            $request->userAgent() ?? 'api',
        );

        // Otomatis kirim kode OTP verifikasi email segera setelah registrasi
        try {
            $this->otpService->sendEmailVerificationOtp($result['user']->email);
        } catch (\Exception $e) {
            Log::warning('Gagal mengirimkan OTP verifikasi email saat registrasi: ' . $e->getMessage());
        }

        return ApiResponse::success(
            data: [
                'user' => UserResource::make($result['user'])->resolve($request),
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'Registrasi berhasil. Kode verifikasi OTP telah dikirim ke email Anda.',
            code: Response::HTTP_CREATED,
        );
    }
}
