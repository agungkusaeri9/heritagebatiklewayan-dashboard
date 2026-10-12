<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\AddToCartRequest;
use App\Http\Requests\Api\Cart\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    /**
     * Display the authenticated user's cart items (without pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $cartItems = Cart::query()
            ->where('user_id', $user->id)
            ->with('product')
            ->latest('id')
            ->get();

        $totalItems = (int) $cartItems->sum('quantity');
        $totalWeight = (int) $cartItems->sum(fn ($item) => (int) ($item->product?->weight ?? 0) * (int) $item->quantity);
        $totalPrice = (float) $cartItems->sum(fn ($item) => (float) ($item->product?->price ?? 0) * (int) $item->quantity);

        return ApiResponse::success(
            data: [
                'items' => CartResource::collection($cartItems)->resolve($request),
                'summary' => [
                    'total_items' => $totalItems,
                    'total_weight' => $totalWeight,
                    'total_price' => $totalPrice,
                ],
            ],
            message: 'Berhasil mengambil daftar keranjang.',
        );
    }

    /**
     * Add a product to the authenticated user's cart (max 20 per item).
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);
        $variantInfo = $validated['variant_info'] ?? null;

        $product = Product::find($validated['product_id']);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak tersedia atau sedang dinonaktifkan.',
                code: 422,
            );
        }

        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('variant_info', $variantInfo)
            ->first();

        $currentQty = $cartItem ? $cartItem->quantity : 0;
        $newQty = $currentQty + $quantityToAdd;

        if ($newQty > 20) {
            return ApiResponse::error(
                message: 'Jumlah item dalam keranjang tidak boleh melebihi batas maksimal 20.',
                code: 422,
                errors: [
                    'quantity' => ['Maksimal jumlah barang per varian/produk di keranjang adalah 20.'],
                ],
            );
        }

        if ($product->stock < $newQty) {
            return ApiResponse::error(
                message: "Stok produk tidak mencukupi (stok tersisa: {$product->stock}).",
                code: 422,
                errors: [
                    'quantity' => ["Stok tersisa hanya {$product->stock}."],
                ],
            );
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $newQty]);
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'variant_info' => $variantInfo,
                'quantity' => $newQty,
            ]);
        }

        $cartItem->load('product');

        return ApiResponse::success(
            data: CartResource::make($cartItem)->resolve($request),
            message: 'Produk berhasil ditambahkan ke keranjang.',
            code: Response::HTTP_CREATED,
        );
    }

    /**
     * Update the quantity of a cart item.
     */
    public function update(UpdateCartRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $newQty = (int) $request->validated('quantity');

        $cartItem = Cart::where('user_id', $user->id)->with('product')->find($id);

        if (! $cartItem) {
            return ApiResponse::error(
                message: 'Item keranjang tidak ditemukan.',
                code: 404,
            );
        }

        $product = $cartItem->product;

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak tersedia atau sedang dinonaktifkan.',
                code: 422,
            );
        }

        if ($product->stock < $newQty) {
            return ApiResponse::error(
                message: "Stok produk tidak mencukupi (stok tersisa: {$product->stock}).",
                code: 422,
                errors: [
                    'quantity' => ["Stok tersisa hanya {$product->stock}."],
                ],
            );
        }

        $cartItem->update(['quantity' => $newQty]);

        return ApiResponse::success(
            data: CartResource::make($cartItem->fresh('product'))->resolve($request),
            message: 'Jumlah item keranjang berhasil diperbarui.',
        );
    }

    /**
     * Remove an item from the authenticated user's cart.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $cartItem = Cart::where('user_id', $user->id)->find($id);

        if (! $cartItem) {
            return ApiResponse::error(
                message: 'Item keranjang tidak ditemukan.',
                code: 404,
            );
        }

        $cartItem->delete();

        return ApiResponse::success(
            message: 'Item berhasil dihapus dari keranjang.',
        );
    }
}
