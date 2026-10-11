<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\IndexProductApiRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Display a paginated listing of products.
     */
    public function index(IndexProductApiRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $search = $request->input('search');
        $status = $request->input('status', 'active');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $category = $request->input('category_slug')
            ?? $request->input('category_id')
            ?? $request->input('category');

        $products = $this->productRepository->getPaginatedProducts(
            perPage: $perPage,
            search: $search,
            status: $status,
            sortColumn: $sort,
            sortDirection: $direction,
            category: $category,
        );

        return ApiResponse::paginate(
            paginator: $products,
            data: ProductResource::collection($products)->resolve($request),
            message: 'Berhasil mengambil daftar produk.',
        );
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findById($id);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: ProductResource::make($product)->resolve(request()),
            message: 'Berhasil mengambil detail produk.',
        );
    }

    /**
     * Display the specified product by its slug.
     */
    public function showBySlug(string $slug, Request $request): JsonResponse
    {
        $product = $this->productRepository->findBySlug($slug);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan.',
                code: 404,
            );
        }

        $ratingStats = $this->reviewRepository->getProductRatingStats($product->id);

        $productData = ProductResource::make($product)->resolve($request);
        $productData['rating_stats'] = $ratingStats;

        return ApiResponse::success(
            data: $productData,
            message: 'Berhasil mengambil detail produk.',
        );
    }
}
