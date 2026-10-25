<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\ProvinceResource;
use App\Http\Resources\SubdistrictResource;
use App\Http\Responses\ApiResponse;
use App\Models\City;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of all provinces.
     */
    public function provinces(Request $request): JsonResponse
    {
        $provinces = Province::orderBy('province_name')->get();

        return ApiResponse::success(
            data: ProvinceResource::collection($provinces)->resolve($request),
            message: 'Berhasil mengambil daftar provinsi.',
        );
    }

    /**
     * Display a listing of cities (filterable by province_id).
     */
    public function cities(Request $request): JsonResponse
    {
        $query = City::query();

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        $cities = $query->orderBy('city_name')->get();

        return ApiResponse::success(
            data: CityResource::collection($cities)->resolve($request),
            message: 'Berhasil mengambil daftar kota/kabupaten.',
        );
    }

    /**
     * Display a listing of subdistricts (filterable by city_id).
     */
    public function subdistricts(Request $request): JsonResponse
    {
        $query = Subdistrict::query();

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->integer('city_id'));
        }

        $subdistricts = $query->orderBy('subdistrict_name')->get();

        return ApiResponse::success(
            data: SubdistrictResource::collection($subdistricts)->resolve($request),
            message: 'Berhasil mengambil daftar kecamatan.',
        );
    }
}
