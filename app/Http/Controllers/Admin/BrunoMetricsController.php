<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Bruno\BrunoMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrunoMetricsController extends Controller
{
    public function index(Request $request, BrunoMetricsService $svc): View
    {
        $days = (int) $request->query('days', 7);
        if (!in_array($days, [1, 7, 30, 90], true)) {
            $days = 7;
        }

        $metrics = $svc->summary($days);

        return view('admin.bruno-metrics', compact('metrics', 'days'));
    }
}
