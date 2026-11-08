<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVirtualTryOnResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProductVirtualTryOn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    /**
     * Display the landing page try-on examples flagged via is_landing_page (max 3).
     */
    public function tryOnExamples(Request $request): JsonResponse
    {
        $examples = ProductVirtualTryOn::query()
            ->where('is_landing_page', true)
            ->with(['product', 'virtualModel'])
            ->orderBy('id')
            ->limit(3)
            ->get();

        return ApiResponse::success(
            data: ProductVirtualTryOnResource::collection($examples)->resolve($request),
            message: 'Berhasil mengambil contoh Virtual Try-On landing page.',
        );
    }
}
