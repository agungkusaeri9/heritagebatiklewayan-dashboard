<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function getPaginatedCustomers(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator
    {
        $query = Customer::with('user');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('phone', 'like', "%{$search}%");
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($sort === 'name' || $sort === 'email') {
            // Sort by related user table
            $query->join('users', 'customers.user_id', '=', 'users.id')
                  ->orderBy('users.'.$sort, $direction)
                  ->select('customers.*');
        } else {
            $query->orderBy('customers.'.$sort, $direction);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Customer
    {
        return Customer::with(['user', 'addresses'])->find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}
