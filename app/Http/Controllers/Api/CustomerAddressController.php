<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerAddress\StoreCustomerAddressRequest;
use App\Http\Requests\Api\CustomerAddress\UpdateCustomerAddressRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Http\Responses\ApiResponse;
use App\Services\CustomerAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAddressController extends Controller
{
    public function __construct(private CustomerAddressService $customerAddressService) {}

    /**
     * Display the authenticated user's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $this->customerAddressService->listForUser($request->user());

        return ApiResponse::success(
            data: CustomerAddressResource::collection($addresses)->resolve($request),
            message: 'Berhasil mengambil daftar alamat.',
        );
    }

    /**
     * Store a new address for the authenticated user.
     */
    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $address = $this->customerAddressService->createForUser($request->user(), $request->validated());

        return ApiResponse::success(
            data: CustomerAddressResource::make($address)->resolve($request),
            message: 'Alamat berhasil ditambahkan.',
            code: Response::HTTP_CREATED,
        );
    }

    /**
     * Display the specified address of the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $address = $this->customerAddressService->findForUser($request->user(), $id);

        return ApiResponse::success(
            data: CustomerAddressResource::make($address)->resolve($request),
            message: 'Berhasil mengambil detail alamat.',
        );
    }

    /**
     * Update the specified address of the authenticated user.
     */
    public function update(UpdateCustomerAddressRequest $request, int $id): JsonResponse
    {
        $address = $this->customerAddressService->updateForUser($request->user(), $id, $request->validated());

        return ApiResponse::success(
            data: CustomerAddressResource::make($address)->resolve($request),
            message: 'Alamat berhasil diperbarui.',
        );
    }

    /**
     * Set the specified address as the authenticated user's primary address.
     */
    public function setPrimary(Request $request, int $id): JsonResponse
    {
        $address = $this->customerAddressService->setPrimaryForUser($request->user(), $id);

        return ApiResponse::success(
            data: CustomerAddressResource::make($address)->resolve($request),
            message: 'Alamat utama berhasil diubah.',
        );
    }

    /**
     * Remove the specified address of the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->customerAddressService->deleteForUser($request->user(), $id);

        return ApiResponse::success(message: 'Alamat berhasil dihapus.');
    }
}
