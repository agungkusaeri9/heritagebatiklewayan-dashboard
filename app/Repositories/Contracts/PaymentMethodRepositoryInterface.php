<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentMethod;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentMethodRepositoryInterface
{
    public function getPaginatedPaymentMethods(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator;
    public function findById(int $id): ?PaymentMethod;
    public function create(array $data): PaymentMethod;
    public function update(PaymentMethod $paymentMethod, array $data): bool;
    public function delete(PaymentMethod $paymentMethod): bool;
}
