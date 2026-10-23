<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAccountController extends Controller
{
    /**
     * Delete / deactivate the authenticated user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(message: 'Unauthenticated.', code: 401);
        }

        DB::transaction(function () use ($user) {
            $user->update([
                'is_deleted' => true,
            ]);

            if ($user->customer) {
                $user->customer->update([
                    'is_deleted' => true,
                    'is_active' => false,
                ]);
            }

            // Revoke all personal access tokens for this user
            $user->tokens()->delete();
        });

        return ApiResponse::success(
            message: 'Akun Anda berhasil dihapus.'
        );
    }
}
