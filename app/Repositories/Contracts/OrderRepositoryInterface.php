<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;

interface OrderRepositoryInterface
{
    public function countTotalOrders(?Carbon $startDate = null, ?Carbon $endDate = null): int;
    public function sumTotalRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): float;
    public function getOrdersCountByStatus(?Carbon $startDate = null, ?Carbon $endDate = null): array;
    public function getRevenueByPeriod(string $period): array;
    public function getOrdersCountByPeriod(string $period): array;
    public function getRecentOrders(int $limit): \Illuminate\Database\Eloquent\Collection;
}
