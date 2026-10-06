<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function countTotalOrders(?Carbon $startDate = null, ?Carbon $endDate = null): int;

    public function sumTotalRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): float;

    public function getOrdersCountByStatus(?Carbon $startDate = null, ?Carbon $endDate = null): array;

    public function getRevenueByPeriod(string $period): array;

    public function getOrdersCountByPeriod(string $period): array;

    public function getRecentOrders(int $limit): Collection;
}
