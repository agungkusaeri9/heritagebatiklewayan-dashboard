<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\IndexProductReviewRequest;
use App\Http\Requests\Api\Review\StoreProductReviewApiRequest;
use App\Http\Resources\ProductReviewResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductReviewController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Display a paginated listing of reviews for a product by its slug.
     */
    public function index(string $slug, IndexProductReviewRequest $request): JsonResponse
    {
        $product = is_numeric($slug)
            ? ($this->productRepository->findById((int) $slug) ?? $this->productRepository->findBySlug($slug))
            : $this->productRepository->findBySlug($slug);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan.',
                code: 404,
            );
        }

        $perPage = (int) $request->input('per_page', 10);
        $ratingFilter = $request->filled('rating') ? (int) $request->input('rating') : null;

        $reviews = $this->reviewRepository->getPaginatedReviewsForProduct(
            productId: $product->id,
            perPage: $perPage,
            rating: $ratingFilter,
            onlyVisible: true,
        );

        $ratingStats = $this->reviewRepository->getProductRatingStats($product->id);

        return ApiResponse::paginate(
            paginator: $reviews,
            data: ProductReviewResource::collection($reviews)->resolve($request),
            message: 'Berhasil mengambil daftar ulasan produk.',
            meta: [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
                'rating_stats' => $ratingStats,
            ],
        );
    }

    /**
     * Store a new product review for a completed and paid order.
     */
    public function store(StoreProductReviewApiRequest $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $review = $this->reviewRepository->createReview([
            'order_id' => (int) $request->validated('order_id'),
            'product_id' => (int) $request->validated('product_id'),
            'customer_id' => $customer->id,
            'rating' => (int) $request->validated('rating'),
            'comment' => $request->validated('comment'),
            'is_visible' => true,
        ]);

        $review->load(['customer.user', 'product', 'order']);

        return ApiResponse::success(
            data: ProductReviewResource::make($review)->resolve($request),
            message: 'Ulasan produk berhasil dikirim.',
            code: Response::HTTP_CREATED,
        );
    }
}
