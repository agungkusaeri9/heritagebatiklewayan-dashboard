<?php

namespace App\Services;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Carbon\Carbon;

class DashboardService
{
    protected $orderRepository;

    protected $customerRepository;

    protected $productRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
    }

    public function getDashboardData(string $filter = 'this_week'): array
    {
        $isYearFilter = str_starts_with($filter, 'year-');

        if ($isYearFilter) {
            $year = (int) str_replace('year-', '', $filter);
            $startDate = Carbon::createFromDate($year)->startOfYear();
            $endDate = Carbon::createFromDate($year)->endOfYear();
            $aggregationPeriod = $filter;
        } else {
            $startDate = match ($filter) {
                'today' => Carbon::today(),
                'this_week' => Carbon::now()->startOfWeek(),
                'this_month' => Carbon::now()->startOfMonth(),
                default => Carbon::now()->startOfWeek(),
            };
            $endDate = Carbon::now();
            $aggregationPeriod = $filter;
        }

        $revenueChart = $this->orderRepository->getRevenueByPeriod($aggregationPeriod);
        $ordersChart = $this->orderRepository->getOrdersCountByPeriod($aggregationPeriod);
        $statusCounts = $this->orderRepository->getOrdersCountByStatus($startDate, $endDate);

        $dates = array_keys($revenueChart);
        $revenueData = array_values($revenueChart);
        $ordersData = array_values($ordersChart);

        return [
            'summary' => [
                'totalOrders' => $this->orderRepository->countTotalOrders($startDate, $endDate),
                'totalRevenue' => $this->orderRepository->sumTotalRevenue($startDate, $endDate),
                'totalCustomers' => $this->customerRepository->countTotal(), // Total customers typically isn't filtered, but we could
                'totalProducts' => $this->productRepository->countProducts(),
            ],
            'chartData' => [
                'labels' => $dates,
                'revenue' => $revenueData,
                'orders' => $ordersData,
                'status' => [
                    $statusCounts['pending'] ?? 0,
                    $statusCounts['processing'] ?? 0,
                    $statusCounts['success'] ?? 0,
                    $statusCounts['failed'] ?? 0,
                ],
            ],
            'recentOrders' => $this->orderRepository->getRecentOrders(5),
        ];
    }
}
