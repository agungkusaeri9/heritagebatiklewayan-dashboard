<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(message: 'Unauthenticated.', code: 401);
        }

        $user->loadMissing(['customer', 'roles']);

        return ApiResponse::success(
            data: UserResource::make($user)->resolve($request),
            message: 'Berhasil mengambil data profil.',
        );
    }
}
