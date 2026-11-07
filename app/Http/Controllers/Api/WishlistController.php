<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Wishlist\StoreWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    /**
     * Display the authenticated user's wishlist items (without pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $wishlistItems = Wishlist::query()
            ->where('user_id', $user->id)
            ->with('product')
            ->latest('id')
            ->get();

        return ApiResponse::success(
            data: [
                'items' => WishlistResource::collection($wishlistItems)->resolve($request),
                'total' => $wishlistItems->count(),
            ],
            message: 'Berhasil mengambil daftar wishlist.',
        );
    }

    /**
     * Add a product to the authenticated user's wishlist (idempotent).
     */
    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $user = $request->user();
        $productId = (int) $request->validated('product_id');

        $product = Product::find($productId);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak tersedia atau sedang dinonaktifkan.',
                code: 422,
            );
        }

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $wasRecentlyCreated = $wishlist->wasRecentlyCreated;
        $wishlist->load('product');

        return ApiResponse::success(
            data: WishlistResource::make($wishlist)->resolve($request),
            message: $wasRecentlyCreated
                ? 'Produk berhasil ditambahkan ke wishlist.'
                : 'Produk sudah ada di wishlist Anda.',
            code: $wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK,
        );
    }

    /**
     * Remove a product from the authenticated user's wishlist.
     */
    public function destroy(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();

        $wishlist = Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (! $wishlist) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan di wishlist Anda.',
                code: 404,
            );
        }

        $wishlist->delete();

        return ApiResponse::success(
            message: 'Produk berhasil dihapus dari wishlist.',
        );
    }
}
