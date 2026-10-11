<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreSettingResource;
use App\Http\Responses\ApiResponse;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    /**
     * Display all store settings (information, contacts, social media, policies).
     */
    public function index(Request $request): JsonResponse
    {
        $setting = StoreSetting::first();

        if (! $setting) {
            $setting = StoreSetting::create([
                'name' => 'Toko BatikKu',
                'whatsapp' => '6281234567890',
            ]);
        }

        return ApiResponse::success(
            data: StoreSettingResource::make($setting)->resolve($request),
            message: 'Berhasil mengambil pengaturan toko.',
        );
    }

    /**
     * Display only the privacy policy and terms/policy content.
     */
    public function privacyPolicy(Request $request): JsonResponse
    {
        $setting = StoreSetting::first();

        return ApiResponse::success(
            data: [
                'privacy_policy' => $setting?->privacy_policy ?? '',
                'policy' => $setting?->policy ?? '',
                'updated_at' => $setting?->updated_at?->toISOString(),
            ],
            message: 'Berhasil mengambil kebijakan privasi dan ketentuan toko.',
        );
    }
}
