<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SendEmailVerificationOtpRequest;
use App\Http\Requests\Api\Auth\VerifyEmailVerificationOtpRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Send OTP for email verification.
     */
    public function sendOtp(SendEmailVerificationOtpRequest $request): JsonResponse
    {
        $email = $request->getEmail();

        $this->otpService->sendEmailVerificationOtp($email);

        return ApiResponse::success(
            data: [
                'email' => $email,
            ],
            message: 'Kode OTP verifikasi email telah dikirim ke email Anda.'
        );
    }

    /**
     * Verify OTP and mark email as verified.
     */
    public function verifyOtp(VerifyEmailVerificationOtpRequest $request): JsonResponse
    {
        $email = $request->getEmail();

        $user = $this->otpService->verifyEmailOtp($email, $request->validated('otp'));

        return ApiResponse::success(
            data: [
                'user' => UserResource::make($user->loadMissing(['customer', 'roles']))->resolve($request),
            ],
            message: 'Alamat email berhasil diverifikasi.'
        );
    }
}
