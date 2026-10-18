<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResetPasswordWithOtpRequest;
use App\Http\Responses\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Reset user password using verified reset token.
     */
    public function __invoke(ResetPasswordWithOtpRequest $request): JsonResponse
    {
        $this->otpService->resetPassword(
            $request->validated('email'),
            $request->validated('reset_token'),
            $request->validated('password')
        );

        return ApiResponse::success(
            message: 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.'
        );
    }
}
