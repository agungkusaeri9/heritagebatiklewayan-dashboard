<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function getPaginatedCustomers(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator;
    public function findById(int $id): ?Customer;
    public function create(array $data): Customer;
    public function update(Customer $customer, array $data): bool;
    public function delete(Customer $customer): bool;
}
