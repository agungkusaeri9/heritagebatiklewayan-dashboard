<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display a listing of active promotional banners.
     */
    public function index(Request $request): JsonResponse
    {
        $banners = Banner::query()
            ->active()
            ->with([
                'product:id,name,slug,price,primary_image',
                'category:id,name,slug',
            ])
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::success(
            data: BannerResource::collection($banners)->resolve($request),
            message: 'Berhasil mengambil daftar banner promosi.',
        );
    }
}
