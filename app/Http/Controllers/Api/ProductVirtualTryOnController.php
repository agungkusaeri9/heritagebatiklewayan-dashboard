<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VirtualTryOn\IndexProductVirtualTryOnApiRequest;
use App\Http\Requests\Api\VirtualTryOn\StoreProductVirtualTryOnApiRequest;
use App\Http\Resources\ProductVirtualTryOnResource;
use App\Http\Resources\VirtualTryOnModelResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProductVirtualTryOn;
use App\Models\VirtualTryOnModel;
use App\Services\ProductVirtualTryOnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductVirtualTryOnController extends Controller
{
    public function __construct(
        protected ProductVirtualTryOnService $tryOnService
    ) {}

    /**
     * Resolve the authenticated user from request (supporting Sanctum bearer token or session).
     */
    protected function resolveUser(Request $request): ?\App\Models\User
    {
        return $request->user() ?? auth('sanctum')->user();
    }

    /**
     * Get paginated virtual try-on results.
     */
    public function index(IndexProductVirtualTryOnApiRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $user = $this->resolveUser($request);

        $productId = $request->input('product_id') ?? $request->route('id') ?? $request->route('product_id');

        $filters = [
            'search' => $request->input('search'),
            'product_id' => $productId ? (int) $productId : null,
            'gender' => $request->input('gender'),
            'status' => $request->input('status'),
        ];

        $type = $request->input('type');

        if ($type === 'my') {
            if (! $user) {
                return ApiResponse::error('Silakan login terlebih dahulu untuk melihat riwayat try-on Anda.', 401);
            }
            $filters['user_id'] = $user->id;
        } elseif ($type === 'default') {
            $filters['without_user'] = true;
        } elseif ($type === 'all') {
            // Keep all without restricting to single user
        } else {
            // Default behavior:
            // 1. If product_id is specified: show default models and (if logged in) user's try-ons for this product
            // 2. If no product_id: if logged in show user's history, if guest show default showcase models
            if (! $filters['product_id']) {
                if ($user) {
                    $filters['user_id'] = $user->id;
                } else {
                    $filters['without_user'] = true;
                }
            }
        }

        $tryOns = $this->tryOnService->getTryOns($filters, $perPage);

        $meta = [];
        if ($user) {
            $meta['quota'] = $this->tryOnService->getUserQuotaStatus($user);
        }

        return ApiResponse::paginate(
            paginator: $tryOns,
            data: ProductVirtualTryOnResource::collection($tryOns)->resolve($request),
            message: 'Berhasil mengambil daftar virtual try-on.',
            meta: $meta,
        );
    }

    /**
     * Get list of active avatar/mannequin models available for virtual try-on.
     */
    public function models(): JsonResponse
    {
        $models = VirtualTryOnModel::active()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: VirtualTryOnModelResource::collection($models),
            message: 'Berhasil mengambil daftar model avatar.',
        );
    }

    /**
     * Store and dispatch a new virtual try-on request for the authenticated user.
     */
    public function store(StoreProductVirtualTryOnApiRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return ApiResponse::error('Silakan login terlebih dahulu untuk menggunakan fitur Virtual Try-On.', 401);
        }

        try {
            $tryOn = $this->tryOnService->createUserTryOn(
                $request->validated(),
                $user,
                $request->file('model_photo')
            );
            $tryOn->load(['product', 'virtualModel', 'user']);

            return ApiResponse::success(
                data: new ProductVirtualTryOnResource($tryOn),
                message: 'Permintaan Virtual Try-On berhasil dibuat dan sedang diproses dalam antrean latar belakang.',
                code: 201,
                meta: [
                    'quota' => $this->tryOnService->getUserQuotaStatus($user),
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('API Store Virtual Try-On Error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'payload' => $request->validated(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Gagal memproses Virtual Try-On: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get details of a specific virtual try-on.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $tryOn = $this->tryOnService->getTryOnById($id);

        if (! $tryOn) {
            return ApiResponse::error('Data Virtual Try-On tidak ditemukan.', 404);
        }

        // Authorization check: if try-on belongs to a specific user, only the owner (or admin) can view
        if ($tryOn->user_id !== null) {
            $user = $this->resolveUser($request);
            if (! $user || ((int) $user->id !== (int) $tryOn->user_id && ! $user->can('view product virtual try ons'))) {
                return ApiResponse::error('Anda tidak memiliki izin untuk melihat data Virtual Try-On ini.', 403);
            }
        }

        return ApiResponse::success(
            data: new ProductVirtualTryOnResource($tryOn),
            message: 'Berhasil mengambil detail virtual try-on.',
        );
    }

    /**
     * Check progress and status of a virtual try-on generation.
     */
    public function progress(int $id, Request $request): JsonResponse
    {
        $tryOn = ProductVirtualTryOn::find($id);

        if (! $tryOn) {
            return ApiResponse::error('Data Virtual Try-On tidak ditemukan.', 404);
        }

        if ($tryOn->user_id !== null) {
            $user = $this->resolveUser($request);
            if (! $user || ((int) $user->id !== (int) $tryOn->user_id && ! $user->can('view product virtual try ons'))) {
                return ApiResponse::error('Anda tidak memiliki izin untuk melihat progress ini.', 403);
            }
        }

        return ApiResponse::success(
            data: [
                'id' => $tryOn->id,
                'status' => $tryOn->status, // queued, processing, completed, failed
                'progress' => (int) $tryOn->progress,
                'step_label' => $tryOn->step_label,
                'photo_url' => $tryOn->photo_url,
                'video_url' => $tryOn->video_url,
                'generate_video' => (bool) $tryOn->generate_video,
                'is_completed' => $tryOn->status === 'completed',
                'is_failed' => $tryOn->status === 'failed',
                'error_message' => $tryOn->error_message,
                'updated_at' => $tryOn->updated_at?->toISOString(),
            ],
            message: 'Status progress virtual try-on berhasil diambil.',
        );
    }

    /**
     * Retry a failed virtual try-on task.
     */
    public function retry(int $id, Request $request): JsonResponse
    {
        $tryOn = $this->tryOnService->getTryOnById($id);

        if (! $tryOn) {
            return ApiResponse::error('Data Virtual Try-On tidak ditemukan.', 404);
        }

        $user = $this->resolveUser($request);
        if (! $user || ((int) $user->id !== (int) $tryOn->user_id && ! $user->can('create product virtual try ons'))) {
            return ApiResponse::error('Anda tidak memiliki izin untuk mencoba ulang generasi ini.', 403);
        }

        if ($tryOn->status !== 'failed') {
            return ApiResponse::error('Hanya tugas dengan status gagal yang dapat dicoba ulang.', 422);
        }

        try {
            $this->tryOnService->retryGeneration($tryOn);

            return ApiResponse::success(
                data: new ProductVirtualTryOnResource($tryOn->fresh()),
                message: 'Generasi Virtual Try-On sedang dicoba ulang dalam antrean.',
            );
        } catch (\Throwable $e) {
            Log::error('API Retry Virtual Try-On Error: '.$e->getMessage(), ['id' => $id]);

            return ApiResponse::error('Gagal mencoba ulang generasi Virtual Try-On: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get quota information and limits for the authenticated user.
     */
    public function quota(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return ApiResponse::error('Silakan login terlebih dahulu.', 401);
        }

        return ApiResponse::success(
            data: $this->tryOnService->getUserQuotaStatus($user),
            message: 'Status kuota Virtual Try-On berhasil diambil.',
        );
    }
}
