<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshTokenController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Revoke current token and generate a new access token.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(message: 'Unauthenticated.', code: 401);
        }

        $newToken = $this->authService->refreshToken(
            $user,
            $request->userAgent() ?? 'api',
        );

        return ApiResponse::success(
            data: [
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ],
            message: 'Token berhasil diperbarui.',
        );
    }
}
