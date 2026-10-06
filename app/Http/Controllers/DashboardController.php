<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'this_week');
        $data = $this->dashboardService->getDashboardData($filter);

        $data['currentFilter'] = $filter;

        $currentYear = Carbon::now()->year;
        $data['availableYears'] = range($currentYear, $currentYear - 4);

        return Inertia::render('Dashboard', $data);
    }
}
