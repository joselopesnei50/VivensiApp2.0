<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProspect;
use App\Models\Prospect;
use App\Models\SponsorshipDeal;
use App\Services\LeadSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProspectingController extends Controller
{
    public function index(Request $request)
    {
        $tenantId  = Auth::user()->tenant_id;
        $status    = $request->query('status'); // filter: raw | analyzed | contacted

        $base = Prospect::query()->when(
            $tenantId,
            fn ($q) => $q->where('tenant_id', $tenantId),
            fn ($q) => $q->whereNull('tenant_id')
        );

        $prospects = (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'analyzed' THEN 0 WHEN 'raw' THEN 1 ELSE 2 END")
            ->orderBy('lead_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats — consultas separadas para contar no total, não só na página atual
        $stats = [
            'total'     => (clone $base)->count(),
            'raw'       => (clone $base)->where('status', 'raw')->count(),
            'analyzed'  => (clone $base)->where('status', 'analyzed')->count(),
            'contacted' => (clone $base)->where('status', 'contacted')->count(),
            'hot'       => (clone $base)->where('lead_score', '>=', 80)->count(),
        ];

        return view('prospecting.index', compact('prospects', 'stats', 'status'));
    }

    public function search(Request $request, LeadSearchService $searchService)
    {
        $mode = $request->input('mode', 'maps'); // 'maps' | 'web'

        $request->validate([
            'term'     => 'required|string|max:100',
            'location' => ($mode === 'maps') ? 'required|string|max:100' : 'nullable|string|max:100',
        ]);

        try {
            if ($mode === 'web') {
                $result = $searchService->searchWeb($request->term, Auth::user()->tenant_id);
                $modeLabel = 'Busca Web';
            } else {
                $result = $searchService->search($request->term, $request->location, Auth::user()->tenant_id);
                $modeLabel = 'Google Maps';
            }

            $msg = "[{$modeLabel}] {$result['new']} novo(s) lead(s) encontrado(s).";
            if ($result['updated'] > 0) {
                $msg .= " {$result['updated']} já existia(m) e foram atualizados.";
            }
            $msg .= ' A Bruce AI está analisando em segundo plano.';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro na prospecção: ' . $e->getMessage());
        }
    }

    /** Analisa um lead específico (re-dispara o job). */
    public function analyze(int $id)
    {
        $prospect = $this->findForTenant($id);
        ProcessProspect::dispatch($prospect)->onQueue('default');

        return back()->with('success', 'Análise da Bruce AI reiniciada para este lead.');
    }

    /** Analisa em lote todos os leads ainda em status raw. */
    public function analyzeAll()
    {
        $tenantId = Auth::user()->tenant_id;

        $rawLeads = Prospect::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->where('status', 'raw')
            ->get();

        foreach ($rawLeads as $prospect) {
            ProcessProspect::dispatch($prospect)->onQueue('default');
        }

        return back()->with('success', "Bruce AI iniciou a análise de {$rawLeads->count()} lead(s) em fila.");
    }

    /** Converte um lead para o Funil de Patrocínios (SponsorshipDeal). */
    public function convertToDeal(int $id)
    {
        $prospect = $this->findForTenant($id);
        $tenantId = Auth::user()->tenant_id;

        return DB::transaction(function () use ($prospect, $tenantId) {
            SponsorshipDeal::create([
                'tenant_id'      => $tenantId,
                'company_name'   => $prospect->company_name,
                'contact_person' => 'Lead de Prospecção Automática',
                'phone'          => $prospect->phone,
                'email'          => null, // website não é email
                'expected_value' => 0,
                'stage'          => 'prospecting',
                'notes'          => implode("\n\n", array_filter([
                    "Lead convertido da Prospecção Automática.",
                    $prospect->address   ? "Endereço: {$prospect->address}"   : null,
                    $prospect->website   ? "Site: {$prospect->website}"       : null,
                    $prospect->ai_analysis ? "Análise Bruce AI:\n{$prospect->ai_analysis}" : null,
                ])),
            ]);

            $prospect->update(['status' => 'contacted']);

            return back()->with('success', 'Lead enviado para o Funil de Patrocínios com sucesso!');
        });
    }

    public function destroy(int $id)
    {
        $this->findForTenant($id)->delete();

        return back()->with('success', 'Lead removido da lista.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function findForTenant(int $id): Prospect
    {
        $tenantId = Auth::user()->tenant_id;

        return Prospect::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->findOrFail($id);
    }
}
