<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppVersionResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppVersionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AppVersion::query()->where('is_active', true);

        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', strtolower($type));
            }
        }

        if ($request->has('is_latest')) {
            $query->where('is_latest', $request->boolean('is_latest'));
        }

        $query->orderByDesc('is_latest')->orderByDesc('version_code')->orderByDesc('id');

        if ($request->has('per_page')) {
            $perPage = (int) $request->input('per_page', 10);
            $versions = $query->paginate($perPage);

            return ApiResponse::paginate(
                paginator: $versions,
                data: AppVersionResource::collection($versions)->resolve($request),
                message: 'Berhasil mengambil daftar versi aplikasi.',
            );
        }

        $versions = $query->get();

        return ApiResponse::success(
            data: AppVersionResource::collection($versions)->resolve($request),
            message: 'Berhasil mengambil daftar versi aplikasi.',
        );
    }

    public function latest(Request $request): JsonResponse
    {
        $type = strtolower((string) $request->input('type', 'android'));

        $latest = AppVersion::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->orderByDesc('is_latest')
            ->orderByDesc('version_code')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return ApiResponse::error(
                message: "Versi aplikasi untuk platform {$type} belum tersedia.",
                code: 404,
            );
        }

        $data = AppVersionResource::make($latest)->resolve($request);

        $currentVersion = $request->input('version') ?? $request->input('current_version');
        $currentVersionCode = $request->input('version_code') ?? $request->input('current_version_code');

        $needsUpdate = false;
        if ($currentVersionCode !== null) {
            $needsUpdate = (int) $currentVersionCode < (int) $latest->version_code;
        } elseif ($currentVersion !== null) {
            $needsUpdate = version_compare((string) $currentVersion, (string) $latest->version, '<');
        }

        $belowMinVersion = false;
        if ($currentVersion !== null && $latest->min_version) {
            $belowMinVersion = version_compare((string) $currentVersion, (string) $latest->min_version, '<');
        }

        $data['needs_update'] = $needsUpdate;
        $data['force_update'] = $needsUpdate && ($latest->is_force_update || $belowMinVersion);

        return ApiResponse::success(
            data: $data,
            message: 'Berhasil mengambil versi aplikasi terbaru.',
        );
    }

    public function downloadLatest(Request $request): StreamedResponse|JsonResponse
    {
        $type = strtolower((string) $request->input('type', 'android'));

        $latest = AppVersion::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->whereNotNull('file_path')
            ->orderByDesc('is_latest')
            ->orderByDesc('version_code')
            ->orderByDesc('id')
            ->first();

        if (! $latest || ! $latest->file_path || ! Storage::disk('public')->exists($latest->file_path)) {
            return ApiResponse::error(
                message: "File unduhan aplikasi untuk platform {$type} belum tersedia.",
                code: 404,
            );
        }

        return Storage::disk('public')->download(
            $latest->file_path,
            $latest->file_name ?: basename($latest->file_path)
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $version = AppVersion::where('is_active', true)->find($id);

        if (! $version) {
            return ApiResponse::error(
                message: 'Versi aplikasi tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: AppVersionResource::make($version)->resolve($request),
            message: 'Berhasil mengambil detail versi aplikasi.',
        );
    }

    public function download(int $id): StreamedResponse|JsonResponse
    {
        $version = AppVersion::where('is_active', true)->find($id);

        if (! $version || ! $version->file_path || ! Storage::disk('public')->exists($version->file_path)) {
            return ApiResponse::error(
                message: 'File aplikasi tidak ditemukan.',
                code: 404,
            );
        }

        return Storage::disk('public')->download(
            $version->file_path,
            $version->file_name ?: basename($version->file_path)
        );
    }
}
