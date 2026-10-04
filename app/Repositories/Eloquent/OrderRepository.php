<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function countTotalOrders(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        return Order::query()
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->count();
    }

    public function sumTotalRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        return Order::where('status', 'success')
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->sum('total_amount');
    }

    public function getRevenueByPeriod(string $period): array
    {
        return $this->aggregateByPeriod($period, 'sum');
    }

    public function getOrdersCountByPeriod(string $period): array
    {
        return $this->aggregateByPeriod($period, 'count');
    }

    private function aggregateByPeriod(string $period, string $type = 'sum'): array
    {
        $data = [];
        if ($period === 'today') {
            for ($i = 0; $i < 24; $i++) {
                $label = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $query = Order::whereDate('created_at', Carbon::today())->whereRaw('HOUR(created_at) = ?', [$i]);
                if ($type === 'sum') $query->where('status', 'success');
                $data[$label] = $type === 'sum' ? (float) $query->sum('total_amount') : $query->count();
            }
        } elseif (str_starts_with($period, 'year-')) {
            $year = (int) str_replace('year-', '', $period);
            for ($i = 1; $i <= 12; $i++) {
                $label = Carbon::create()->month($i)->format('M');
                $query = Order::whereYear('created_at', $year)->whereMonth('created_at', $i);
                if ($type === 'sum') $query->where('status', 'success');
                $data[$label] = $type === 'sum' ? (float) $query->sum('total_amount') : $query->count();
            }
        } else {
            // this_week or this_month -> group by days
            $days = $period === 'this_month' ? Carbon::now()->daysInMonth : 7;
            $start = $period === 'this_month' ? Carbon::now()->startOfMonth() : Carbon::now()->startOfWeek();
            
            for ($i = 0; $i < $days; $i++) {
                $date = $start->copy()->addDays($i);
                if ($date->isFuture()) break; // Optional: don't show future days in the chart
                
                $label = $date->format('d M');
                $query = Order::whereDate('created_at', $date);
                if ($type === 'sum') $query->where('status', 'success');
                $data[$label] = $type === 'sum' ? (float) $query->sum('total_amount') : $query->count();
            }
        }
        return $data;
    }

    public function getOrdersCountByStatus(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return Order::select('status', DB::raw('count(*) as total'))
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getRecentOrders(int $limit): Collection
    {
        return Order::with(['customer'])
            ->latest()
            ->take($limit)
            ->get();
    }
}
