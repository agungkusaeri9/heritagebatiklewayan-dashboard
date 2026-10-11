<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Authenticate a customer and issue an API token.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginCustomer(
            $request->validated(),
            $request->userAgent() ?? 'api',
        );

        return ApiResponse::success(
            data: [
                'user' => UserResource::make($result['user'])->resolve($request),
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'Login berhasil.',
        );
    }
}
