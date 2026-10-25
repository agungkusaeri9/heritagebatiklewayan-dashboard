<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourierResource;
use App\Http\Responses\ApiResponse;
use App\Models\Courier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    /**
     * Display a listing of all active couriers / shipping types.
     */
    public function index(Request $request): JsonResponse
    {
        $couriers = Courier::where('is_active', true)->get();

        return ApiResponse::success(
            data: CourierResource::collection($couriers)->resolve($request),
            message: 'Berhasil mengambil daftar kurir pengiriman.',
        );
    }
}
