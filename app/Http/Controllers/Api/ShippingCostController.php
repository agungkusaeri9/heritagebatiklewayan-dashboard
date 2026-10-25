<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shipping\CheckShippingCostRequest;
use App\Http\Responses\ApiResponse;
use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ShippingCostController extends Controller
{
    public function __construct(
        protected RajaOngkirService $rajaOngkirService
    ) {}

    /**
     * Check / calculate domestic shipping cost via RajaOngkir.
     */
    public function calculate(CheckShippingCostRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->rajaOngkirService->calculateDomesticCost([
                'origin' => $validated['origin'],
                'destination' => $validated['destination'],
                'weight' => (int) $validated['weight'],
                'courier' => $validated['courier'],
                'price' => $validated['price'] ?? true,
            ]);

            $data = $result['data'] ?? $result;
            $message = $result['meta']['message'] ?? 'Berhasil menghitung ongkos kirim.';

            return ApiResponse::success(
                data: $data,
                message: $message,
            );
        } catch (Throwable $e) {
            $code = $e->getCode();
            $statusCode = ($code >= 400 && $code < 600) ? (int) $code : 400;

            return ApiResponse::error(
                message: $e->getMessage() ?: 'Terjadi kesalahan saat menghitung ongkos kirim.',
                code: $statusCode,
            );
        }
    }

    /**
     * Handle invokable controller call as fallback.
     */
    public function __invoke(CheckShippingCostRequest $request): JsonResponse
    {
        return $this->calculate($request);
    }
}
