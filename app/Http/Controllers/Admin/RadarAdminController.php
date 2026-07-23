<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadarFinding;
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
}
