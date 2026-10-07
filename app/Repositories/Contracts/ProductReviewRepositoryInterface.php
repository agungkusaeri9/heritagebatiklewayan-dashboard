<?php

namespace App\Repositories\Contracts;

use App\Models\ProductReview;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductReviewRepositoryInterface
{
    /**
     * Get paginated reviews for a specific product.
     */
    public function getPaginatedReviewsForProduct(
        int $productId,
        int $perPage = 10,
        ?int $rating = null,
        bool $onlyVisible = true
    ): LengthAwarePaginator;

    /**
     * Get aggregate statistics (average, total, breakdown) for a product's reviews.
     */
    public function getProductRatingStats(int $productId): array;

    /**
     * Check if a customer has purchased and can review a specific product in an order.
     */
    public function canCustomerReviewProduct(int $customerId, int $productId, int $orderId): bool;

    /**
     * Create a new product review.
     */
    public function createReview(array $data): ProductReview;

    /**
     * Find a review by ID.
     */
    public function findById(int $id): ?ProductReview;
}
