<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\StoreProductReviewRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function __construct(
        protected ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Store a newly created review from a verified buyer.
     */
    public function store(StoreProductReviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();

        if (! $customer && $user->hasRole('super admin')) {
            // If super admin creating, get the order's customer
            $order = Order::find($validated['order_id']);
            $customerId = $order->customer_id;
        } else {
            $customerId = $customer->id;
        }

        $this->reviewRepository->createReview([
            'order_id' => $validated['order_id'],
            'product_id' => $validated['product_id'],
            'customer_id' => $customerId,
            'rating' => (int) $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Ulasan Anda berhasil dikirimkan. Terima kasih atas ulasan Anda!');
    }

    /**
     * Toggle visibility of a review (admin moderation).
     */
    public function toggle(ProductReview $review): RedirectResponse
    {
        $review->update([
            'is_visible' => ! $review->is_visible,
        ]);

        $status = $review->is_visible ? 'ditampilkan' : 'disembunyikan';

        return back()->with('success', "Ulasan berhasil {$status}.");
    }

    /**
     * Remove the specified review.
     */
    public function destroy(ProductReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
