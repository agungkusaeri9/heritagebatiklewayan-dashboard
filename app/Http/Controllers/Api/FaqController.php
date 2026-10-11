<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Http\Responses\ApiResponse;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of active FAQs with search, category filter, and optional pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Faq::query()->where('is_active', true);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $query->orderBy('order', 'asc')->orderBy('id', 'asc');

        $categories = Faq::where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        if ($request->has('per_page')) {
            $perPage = (int) $request->input('per_page', 10);
            $faqs = $query->paginate($perPage);

            return ApiResponse::paginate(
                paginator: $faqs,
                data: FaqResource::collection($faqs)->resolve($request),
                message: 'Berhasil mengambil daftar FAQ.',
                meta: ['categories' => $categories],
            );
        }

        $faqs = $query->get();

        if ($request->boolean('group_by_category')) {
            $grouped = $faqs->groupBy('category')->map(function ($items) use ($request) {
                return FaqResource::collection($items)->resolve($request);
            });

            return ApiResponse::success(
                data: $grouped,
                message: 'Berhasil mengambil daftar FAQ.',
                meta: ['categories' => $categories],
            );
        }

        return ApiResponse::success(
            data: FaqResource::collection($faqs)->resolve($request),
            message: 'Berhasil mengambil daftar FAQ.',
            meta: ['categories' => $categories],
        );
    }

    /**
     * Display the specified FAQ.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $faq = Faq::where('is_active', true)->find($id);

        if (! $faq) {
            return ApiResponse::error(
                message: 'FAQ tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: FaqResource::make($faq)->resolve($request),
            message: 'Berhasil mengambil detail FAQ.',
        );
    }
}
