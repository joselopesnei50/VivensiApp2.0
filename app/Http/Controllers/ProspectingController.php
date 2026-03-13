<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prospect;
use App\Services\LeadSearchService;
use App\Services\GeminiAnalysisService;
use App\Jobs\ProcessProspect;
use Illuminate\Support\Facades\Auth;

class ProspectingController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $prospects = Prospect::query()
            ->when($tenantId, function($q) use ($tenantId) {
                return $q->where('tenant_id', $tenantId);
            }, function($q) {
                return $q->whereNull('tenant_id');
            })
            ->orderBy('lead_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('prospecting.index', compact('prospects'));
    }

    public function search(Request $request, LeadSearchService $searchService)
    {
        $request->validate([
            'term' => 'required|string|max:100',
            'location' => 'required|string|max:100',
        ]);

        $tenantId = Auth::user()->tenant_id;

        try {
            $count = $searchService->search($request->term, $request->location, $tenantId);
            
            // Dispatch analysis jobs for new raw prospects
            $newProspects = Prospect::query()
                ->when($tenantId, function($q) use ($tenantId) {
                    return $q->where('tenant_id', $tenantId);
                }, function($q) {
                    return $q->whereNull('tenant_id');
                })
                ->where('status', 'raw')
                ->get();

            foreach ($newProspects as $prospect) {
                ProcessProspect::dispatch($prospect);
            }

            return back()->with('success', "$count novos potenciais clientes encontrados e enviados para análise da Bruce AI.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro na prospecção: ' . $e->getMessage());
        }
    }

    public function analyze($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $prospect = Prospect::where('id', $id)
            ->when($tenantId, function($q) use ($tenantId) {
                return $q->where('tenant_id', $tenantId);
            }, function($q) {
                return $q->whereNull('tenant_id');
            })
            ->firstOrFail();

        ProcessProspect::dispatch($prospect);

        return back()->with('success', 'Análise da Bruce AI reiniciada para este lead.');
    }

    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $prospect = Prospect::where('id', $id)
            ->when($tenantId, function($q) use ($tenantId) {
                return $q->where('tenant_id', $tenantId);
            }, function($q) {
                return $q->whereNull('tenant_id');
            })
            ->firstOrFail();

        $prospect->delete();

        return back()->with('success', 'Lead removido da lista.');
    }
}
