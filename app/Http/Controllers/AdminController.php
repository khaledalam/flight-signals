<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Illuminate\View\View;

class AdminController
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function index(): View
    {
        $stats = $this->adminService->getStats();
        $recentFlights = $this->adminService->getRecentFlights();
        $jobs = $this->adminService->getJobCounts();
        $env = $this->adminService->getEnv();

        return view('admin', [
            'stats' => $stats,
            'recentFlights' => $recentFlights,
            'failedJobs' => $jobs['failed'],
            'pendingJobs' => $jobs['pending'],
            'env' => $env,
        ]);
    }
}
