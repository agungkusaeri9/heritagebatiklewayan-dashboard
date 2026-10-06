<?php

namespace App\Repositories\Contracts;

interface CourierRepositoryInterface
{
    public function getPaginatedCouriers(int $perPage = 10, ?string $search = null, ?string $status = null, string $sort = 'created_at', string $direction = 'desc');

    public function findById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function findByCode(string $code);
}
