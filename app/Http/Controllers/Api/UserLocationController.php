<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserLocation\UpdateUserLocationRequest;
use App\Http\Resources\UserLocationResource;
use App\Http\Responses\ApiResponse;
use App\Services\GeoapifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLocationController extends Controller
{
    /**
     * Get the authenticated user's current location.
     */
    public function show(Request $request, GeoapifyService $geoapifyService): JsonResponse
    {
        $user = $request->user();
        $location = $user?->location;

        if (! $location) {
            return ApiResponse::success(
                data: null,
                message: 'Lokasi pengguna belum diatur.'
            );
        }

        $addr = trim((string) $location->address);
        $lbl = strtolower(trim((string) $location->label));
        $isCoordString = (bool) preg_match('/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', $addr);

        // Jika lokasi masih bertuliskan Koordinat / koordinat angka / Lokasi Perangkat (GPS), otomatis resolve alamat lengkap via Geoapify
        if (
            $location->latitude !== null
            && $location->longitude !== null
            && (
                $addr === ''
                || $isCoordString
                || str_starts_with(strtolower($addr), 'koordinat')
                || $lbl === 'lokasi perangkat (gps)'
                || $lbl === 'lokasi saat ini'
            )
        ) {
            try {
                $geo = $geoapifyService->getFullAddress((float) $location->latitude, (float) $location->longitude);
                if (! empty($geo['formatted_address'])) {
                    $location->update([
                        'address' => \Illuminate\Support\Str::limit($geo['formatted_address'], 250, ''),
                        'label' => \Illuminate\Support\Str::limit($geo['formatted_address'], 100, ''),
                    ]);
                }
            } catch (\Throwable) {
                // Abaikan jika gagal reverse geocode saat show
            }
        }

        return ApiResponse::success(
            data: UserLocationResource::make($location)->resolve($request),
            message: 'Berhasil mengambil lokasi pengguna.'
        );
    }

    /**
     * Create or update the authenticated user's current location.
     */
    public function update(UpdateUserLocationRequest $request, GeoapifyService $geoapifyService): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $valAddr = trim((string) ($validated['address'] ?? ''));
        $valLbl = strtolower(trim((string) ($validated['label'] ?? '')));
        $isValCoordString = (bool) preg_match('/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', $valAddr);

        if (
            ! empty($validated['latitude'])
            && ! empty($validated['longitude'])
            && (
                $valAddr === ''
                || $isValCoordString
                || str_starts_with(strtolower($valAddr), 'koordinat')
                || $valLbl === 'lokasi perangkat (gps)'
                || $valLbl === 'lokasi saat ini'
            )
        ) {
            try {
                $geo = $geoapifyService->getFullAddress(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
                if (! empty($geo['formatted_address'])) {
                    $validated['address'] = \Illuminate\Support\Str::limit($geo['formatted_address'], 250, '');
                    $validated['label'] = \Illuminate\Support\Str::limit($geo['formatted_address'], 100, '');
                }
            } catch (\Throwable) {
                // Tetap simpan koordinat jika Geoapify gagal diakses
            }
        }

        if (isset($validated['label'])) {
            $validated['label'] = \Illuminate\Support\Str::limit(trim((string) $validated['label']), 100, '');
        }
        if (isset($validated['address'])) {
            $validated['address'] = \Illuminate\Support\Str::limit(trim((string) $validated['address']), 250, '');
        }

        $location = $user->location()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return ApiResponse::success(
            data: UserLocationResource::make($location)->resolve($request),
            message: 'Lokasi pengguna berhasil disimpan.'
        );
    }
}
