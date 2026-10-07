<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductReviewRepository implements ProductReviewRepositoryInterface
{
    /**
     * Get paginated reviews for a specific product.
     */
    public function getPaginatedReviewsForProduct(
        int $productId,
        int $perPage = 10,
        ?int $rating = null,
        bool $onlyVisible = true
    ): LengthAwarePaginator {
        $query = ProductReview::query()
            ->where('product_id', $productId)
            ->with([
                'customer.user:id,name,username',
                'order:id,order_number',
            ]);

        if ($onlyVisible) {
            $query->where('is_visible', true);
        }

        if ($rating !== null && $rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get aggregate statistics (average, total, breakdown) for a product's reviews.
     */
    public function getProductRatingStats(int $productId): array
    {
        $reviewsQuery = ProductReview::query()
            ->where('product_id', $productId)
            ->where('is_visible', true);

        $totalCount = (clone $reviewsQuery)->count();

        if ($totalCount === 0) {
            return [
                'average_rating' => 0.0,
                'total_count' => 0,
                'breakdown' => [
                    5 => ['count' => 0, 'percentage' => 0],
                    4 => ['count' => 0, 'percentage' => 0],
                    3 => ['count' => 0, 'percentage' => 0],
                    2 => ['count' => 0, 'percentage' => 0],
                    1 => ['count' => 0, 'percentage' => 0],
                ],
            ];
        }

        $avgRating = (float) (clone $reviewsQuery)->avg('rating');
        $averageRating = round($avgRating, 1);

        $countsByRating = (clone $reviewsQuery)
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $breakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = (int) ($countsByRating[$star] ?? 0);
            $percentage = $totalCount > 0 ? round(($count / $totalCount) * 100) : 0;
            $breakdown[$star] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'average_rating' => $averageRating,
            'total_count' => $totalCount,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Check if a customer has purchased and can review a specific product in an order.
     */
    public function canCustomerReviewProduct(int $customerId, int $productId, int $orderId): bool
    {
        // 1. Check if order exists for this customer with status 'success' and contains the product
        $hasPurchased = Order::query()
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->where('status', 'success')
            ->whereHas('details', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        if (! $hasPurchased) {
            return false;
        }

        // 2. Check if already reviewed
        $alreadyReviewed = ProductReview::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->exists();

        return ! $alreadyReviewed;
    }

    /**
     * Create a new product review.
     */
    public function createReview(array $data): ProductReview
    {
        return ProductReview::create($data);
    }

    /**
     * Find a review by ID.
     */
    public function findById(int $id): ?ProductReview
    {
        return ProductReview::with(['customer.user', 'order', 'product'])->find($id);
    }
}
