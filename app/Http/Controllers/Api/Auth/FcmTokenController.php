<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * Simpan atau perbarui FCM Token perangkat user saat login / buka aplikasi.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->input('fcm_token'),
        ]);

        return ApiResponse::success(
            data: ['fcm_token' => $user->fcm_token],
            message: 'FCM Token berhasil diperbarui.'
        );
    }
}
