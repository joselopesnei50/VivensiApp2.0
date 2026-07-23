<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadarFinding;
use App\Services\Radar\AutoApproveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RadarAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'novo');

        $findings = RadarFinding::query()
            ->when($status !== 'todos', fn($q) => $q->where('status', $status))
            ->with('curator')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $counts = [
            'novo'      => RadarFinding::where('status', 'novo')->count(),
            'aprovado'  => RadarFinding::where('status', 'aprovado')->count(),
            'rejeitado' => RadarFinding::where('status', 'rejeitado')->count(),
        ];

        return view('admin.radar.index', compact('findings', 'status', 'counts'));
    }

    public function aprovar(int $finding): RedirectResponse
    {
        RadarFinding::findOrFail($finding)->update([
            'status'     => 'aprovado',
            'curated_by' => auth()->id(),
            'curated_at' => now(),
        ]);

        return back()->with('success', 'Achado aprovado.');
    }

    public function rejeitar(int $finding): RedirectResponse
    {
        RadarFinding::findOrFail($finding)->update([
            'status'     => 'rejeitado',
            'curated_by' => auth()->id(),
            'curated_at' => now(),
        ]);

        return back()->with('success', 'Achado rejeitado.');
    }

    public function qualidade(AutoApproveService $service)
    {
        $keywordStats = $service->keywordQualityStats();
        $sourceStats  = $service->sourceQualityStats();

        $autoApproved = RadarFinding::where('auto_approved', true)->count();
        $pendentes    = RadarFinding::where('status', 'novo')
            ->where('is_relevant', true)
            ->whereNotNull('ai_processed_at')
            ->count();

        $flaggedKeywords = array_filter($keywordStats, fn($s) => $s['flagged']);

        return view('admin.radar.qualidade', compact(
            'keywordStats', 'sourceStats', 'autoApproved', 'pendentes', 'flaggedKeywords'
        ));
    }
}
