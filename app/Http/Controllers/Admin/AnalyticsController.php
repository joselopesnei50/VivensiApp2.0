<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteAnalyticsService;
use Illuminate\Http\Request;

/** Métricas de acesso ao sistema (site público + área logada). */
class AnalyticsController extends Controller
{
    public function index(Request $request, SiteAnalyticsService $analytics)
    {
        return view('admin.analytics.index', [
            'm'       => $analytics->summary((int) $request->query('dias', 30), (string) $request->query('area', 'tudo')),
            'periods' => SiteAnalyticsService::PERIODS,
            'areas'   => SiteAnalyticsService::AREAS,
        ]);
    }
}
