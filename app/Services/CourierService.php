<?php

namespace App\Services;

use App\Repositories\Contracts\CourierRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Exception;

class CourierService
{
    public function __construct(
        protected CourierRepositoryInterface $courierRepository
    ) {}

    public function getPaginatedCouriers(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc')
    {
        return $this->courierRepository->getPaginatedCouriers($perPage, $search, $status, $sort, $direction);
    }

    public function createCourier(array $data)
    {
        if (isset($data['logo_file'])) {
            $path = $data['logo_file']->store('couriers', 'public');
            $data['logo'] = $path;
        }

        return $this->courierRepository->create($data);
    }

    public function updateCourier(int $id, array $data)
    {
        $courier = $this->courierRepository->findById($id);

        if (isset($data['logo_file'])) {
            // Delete old logo
            if ($courier->logo && Storage::disk('public')->exists($courier->logo)) {
                Storage::disk('public')->delete($courier->logo);
            }
            $path = $data['logo_file']->store('couriers', 'public');
            $data['logo'] = $path;
        } else if (array_key_exists('logo', $data) && $data['logo'] === null) {
            // Remove logo
            if ($courier->logo && Storage::disk('public')->exists($courier->logo)) {
                Storage::disk('public')->delete($courier->logo);
            }
        }

        return $this->courierRepository->update($id, $data);
    }

    public function deleteCourier(int $id)
    {
        $courier = $this->courierRepository->findById($id);
        if ($courier->logo && Storage::disk('public')->exists($courier->logo)) {
            Storage::disk('public')->delete($courier->logo);
        }
        return $this->courierRepository->delete($id);
    }
}
