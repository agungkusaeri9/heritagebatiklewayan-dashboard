<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserLocation\UpdateUserLocationRequest;
use App\Http\Resources\UserLocationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLocationController extends Controller
{
    /**
     * Get the authenticated user's current location.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $location = $user?->location;

        if (! $location) {
            return ApiResponse::success(
                data: null,
                message: 'Lokasi pengguna belum diatur.'
            );
        }

        return ApiResponse::success(
            data: UserLocationResource::make($location)->resolve($request),
            message: 'Berhasil mengambil lokasi pengguna.'
        );
    }

    /**
     * Create or update the authenticated user's current location.
     */
    public function update(UpdateUserLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        $location = $user->location()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return ApiResponse::success(
            data: UserLocationResource::make($location)->resolve($request),
            message: 'Lokasi pengguna berhasil disimpan.'
        );
    }
}
