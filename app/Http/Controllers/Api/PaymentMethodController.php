<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethodRepositoryInterface $paymentMethodRepository
    ) {}

    /**
     * Display a listing of all active payment methods.
     */
    public function index(Request $request): JsonResponse
    {
        $paymentMethods = $this->paymentMethodRepository->getAllActive();

        return ApiResponse::success(
            data: PaymentMethodResource::collection($paymentMethods)->resolve($request),
            message: 'Berhasil mengambil daftar metode pembayaran.',
        );
    }

    /**
     * Display the specified payment method.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $paymentMethod = $this->paymentMethodRepository->findById($id);

        if (! $paymentMethod || ! $paymentMethod->is_active) {
            return ApiResponse::error(
                message: 'Metode pembayaran tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: PaymentMethodResource::make($paymentMethod)->resolve($request),
            message: 'Berhasil mengambil detail metode pembayaran.',
        );
    }
}
