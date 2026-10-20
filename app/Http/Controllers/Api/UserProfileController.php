<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile and completeness status.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(message: 'Unauthenticated.', code: 401);
        }

        $user->loadMissing(['customer', 'roles']);

        return ApiResponse::success(
            data: UserResource::make($user)->resolve($request),
            message: 'Berhasil mengambil data profil.'
        );
    }

    /**
     * Complete or update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(message: 'Unauthenticated.', code: 401);
        }

        $validated = $request->validated();

        // 1. Update User basic info
        $user->name = $validated['name'];
        $user->save();

        // 2. Handle avatar if uploaded as file or passed as url/empty
        $customerData = [
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
        ];

        $oldAvatar = $user->customer?->avatar;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if stored locally in public disk
            if ($oldAvatar && ! filter_var($oldAvatar, FILTER_VALIDATE_URL)) {
                $relativeStoragePath = str_replace('/storage/', '', $oldAvatar);
                if (Storage::disk('public')->exists($relativeStoragePath)) {
                    Storage::disk('public')->delete($relativeStoragePath);
                }
            }

            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public');
            $customerData['avatar'] = Storage::url($path);
        } elseif ($request->exists('avatar')) {
            $avatarValue = $validated['avatar'] ?? null;
            if (empty($avatarValue)) {
                // Remove existing avatar if requested
                if ($oldAvatar && ! filter_var($oldAvatar, FILTER_VALIDATE_URL)) {
                    $relativeStoragePath = str_replace('/storage/', '', $oldAvatar);
                    if (Storage::disk('public')->exists($relativeStoragePath)) {
                        Storage::disk('public')->delete($relativeStoragePath);
                    }
                }
                $customerData['avatar'] = null;
            } else {
                $customerData['avatar'] = $avatarValue;
            }
        }

        $user->customer()->updateOrCreate(
            ['user_id' => $user->id],
            $customerData
        );

        $user->load(['customer', 'roles']);

        return ApiResponse::success(
            data: UserResource::make($user)->resolve($request),
            message: 'Profil Anda berhasil diperbarui.'
        );
    }
}
