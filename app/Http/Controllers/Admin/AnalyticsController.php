<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductAnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request, ProductAnalyticsService $analytics)
    {
        return view('admin.analytics.index', [
            'a'       => $analytics->build((int) $request->query('dias', 30)),
            'periods' => ProductAnalyticsService::PERIODS,
        ]);
    }
}
