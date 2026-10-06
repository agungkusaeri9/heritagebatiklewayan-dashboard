<?php

namespace App\Repositories\Eloquent;

use App\Models\Courier;
use App\Repositories\Contracts\CourierRepositoryInterface;

class CourierRepository implements CourierRepositoryInterface
{
    public function getPaginatedCouriers(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc')
    {
        $query = Courier::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== 'all') {
            $isActive = $status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findById(int $id)
    {
        return Courier::findOrFail($id);
    }

    public function create(array $data)
    {
        return Courier::create($data);
    }

    public function update(int $id, array $data)
    {
        $courier = Courier::findOrFail($id);
        $courier->update($data);

        return $courier;
    }

    public function delete(int $id)
    {
        return Courier::destroy($id);
    }

    public function findByCode(string $code)
    {
        return Courier::where('code', $code)->first();
    }
}
