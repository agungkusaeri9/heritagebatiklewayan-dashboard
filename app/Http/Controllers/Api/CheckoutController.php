<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\CheckoutRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Responses\ApiResponse;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    /**
     * Process order checkout.
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $order = $this->checkoutService->checkout($request->user(), $request->validated());

        return ApiResponse::success(
            data: OrderDetailResource::make($order)->resolve($request),
            message: 'Pesanan berhasil dibuat.',
            code: Response::HTTP_CREATED,
        );
    }

    /**
     * Calculate and preview checkout summary without placing the order.
     */
    public function preview(CheckoutRequest $request): JsonResponse
    {
        $previewData = $this->checkoutService->preview($request->user(), $request->validated());

        return ApiResponse::success(
            data: $previewData,
            message: 'Berhasil memuat ringkasan checkout.',
        );
    }
}
