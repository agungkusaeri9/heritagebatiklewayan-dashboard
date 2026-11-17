<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Http\Responses\ApiResponse;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Display a listing of stores.
     */
    public function index(Request $request): JsonResponse
    {
        $stores = Store::query()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: StoreResource::collection($stores)->resolve($request),
            message: 'Berhasil mengambil daftar toko.',
        );
    }

    /**
     * Display the specified store by ID.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $store = Store::query()->find($id);

        if (! $store) {
            return ApiResponse::error(
                message: 'Toko tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: StoreResource::make($store)->resolve($request),
            message: 'Berhasil mengambil detail toko.',
        );
    }
}
