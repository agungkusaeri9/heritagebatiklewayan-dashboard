<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Register a new customer account.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerCustomer(
            $request->validated(),
            $request->userAgent() ?? 'api',
        );

        return ApiResponse::success(
            data: [
                'user' => UserResource::make($result['user'])->resolve($request),
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'Registrasi berhasil.',
            code: Response::HTTP_CREATED,
        );
    }
}
