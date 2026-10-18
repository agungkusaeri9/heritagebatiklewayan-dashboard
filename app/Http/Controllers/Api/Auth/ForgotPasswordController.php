<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Responses\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Send OTP to customer's email for password recovery.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->otpService->sendPasswordResetOtp($request->validated('email'));

        return ApiResponse::success(
            data: [
                'email' => $request->validated('email'),
            ],
            message: 'Kode OTP pemulihan kata sandi telah dikirim ke email Anda.'
        );
    }

    /**
     * Verify OTP and return temporary reset token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $resetToken = $this->otpService->verifyPasswordResetOtp(
            $request->validated('email'),
            $request->validated('otp')
        );

        return ApiResponse::success(
            data: [
                'email' => $request->validated('email'),
                'reset_token' => $resetToken,
            ],
            message: 'Kode OTP berhasil diverifikasi.'
        );
    }
}
