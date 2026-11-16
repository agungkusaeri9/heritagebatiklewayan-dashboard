<?php

namespace App\Services;

use App\Models\ProductVirtualTryOn;
use App\Models\Store;
use App\Models\User;
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

    public function getDashboardData(string $filter = 'this_week', ?User $user = null): array
    {
        $isUnrestrictedDefault = $user === null || $user->hasRole('super admin') || ! $user->roles()->exists();
        $isAdminStoreOnly = $user !== null && $user->hasRole(StoreService::ADMIN_STORE_ROLE) && ! $user->hasRole('admin');

        $canViewOrders = ($isUnrestrictedDefault || $user->can('view orders')) && ! $isAdminStoreOnly;
        $canViewCustomers = $isUnrestrictedDefault || $user->can('view customers');
        $canViewProducts = $isUnrestrictedDefault || $user->can('view products') || $user->can('view products by store');
        $canViewStoreProfile = $user !== null && ($user->hasRole('super admin') || $user->can('view store profile'));
        $canViewTryOns = $user !== null && ($user->hasRole('super admin') || $user->can('view product virtual try ons'));

        $isScopedByStore = $user !== null
            && ! $user->hasRole('super admin')
            && ($isAdminStoreOnly || ($user->can('view products by store') && ! $user->can('filter products by store')));

        $ownedStoreIds = null;
        if ($isScopedByStore && $user) {
            $ownedStoreIds = Store::query()
                ->where('user_id', $user->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $totalOrders = 0;
        $totalRevenue = 0;
        $dates = [];
        $revenueData = [];
        $ordersData = [];
        $statusPie = [0, 0, 0, 0];
        $recentOrders = [];

        if ($canViewOrders) {
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
            $totalOrders = $this->orderRepository->countTotalOrders($startDate, $endDate);
            $totalRevenue = $this->orderRepository->sumTotalRevenue($startDate, $endDate);
            $statusPie = [
                $statusCounts['pending'] ?? 0,
                $statusCounts['processing'] ?? 0,
                $statusCounts['success'] ?? 0,
                $statusCounts['failed'] ?? 0,
            ];
            $recentOrders = $this->orderRepository->getRecentOrders(5);
        }

        $totalCustomers = $canViewCustomers ? $this->customerRepository->countTotal() : 0;
        $totalProducts = $canViewProducts ? $this->productRepository->countProducts($ownedStoreIds) : 0;

        $totalVirtualTryOns = 0;
        if ($canViewTryOns) {
            $tryOnQuery = ProductVirtualTryOn::query();
            if ($isScopedByStore) {
                if (empty($ownedStoreIds)) {
                    $tryOnQuery->whereRaw('1 = 0');
                } else {
                    $tryOnQuery->whereHas('product', fn ($q) => $q->whereIn('store_id', $ownedStoreIds));
                }
            }
            $totalVirtualTryOns = $tryOnQuery->count();
        }

        $storeProfile = null;
        if ($canViewStoreProfile && $user) {
            $store = Store::query()->where('user_id', $user->id)->first();
            if ($store) {
                $storeProfile = [
                    'id' => $store->id,
                    'name' => $store->name,
                    'email' => $store->email,
                    'phone' => $store->phone,
                    'whatsapp' => $store->whatsapp,
                    'logo_url' => $store->logo_url,
                ];
            }
        }

        return [
            'summary' => [
                'totalOrders' => $totalOrders,
                'totalRevenue' => $totalRevenue,
                'totalCustomers' => $totalCustomers,
                'totalProducts' => $totalProducts,
                'totalVirtualTryOns' => $totalVirtualTryOns,
            ],
            'chartData' => [
                'labels' => $dates,
                'revenue' => $revenueData,
                'orders' => $ordersData,
                'status' => $statusPie,
            ],
            'recentOrders' => $recentOrders,
            'storeProfile' => $storeProfile,
        ];
    }
}
