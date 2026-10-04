<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function getPaginatedPaymentMethods(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator
    {
        $query = PaymentMethod::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }

    public function update(PaymentMethod $paymentMethod, array $data): bool
    {
        return $paymentMethod->update($data);
    }

    public function delete(PaymentMethod $paymentMethod): bool
    {
        return $paymentMethod->delete();
    }
}
