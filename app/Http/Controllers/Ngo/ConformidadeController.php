<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Jobs\RecalcularConformidadeJob;
use App\Models\AvaliacaoRequisito;
use App\Models\CicloConformidade;
use App\Models\RequisitoLegal;
use App\Models\SnapshotConformidade;
use App\Services\ComplianceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConformidadeController extends Controller
{
    public function __construct(private ComplianceCalculationService $service) {}

    public function dashboard()
    {
        $this->autorizarAdmin();

        $tenantId  = auth()->user()->tenant_id;
        $dashboard = $this->service->dashboard($tenantId);

        $historico = SnapshotConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('snapshotado_em')
            ->limit(12)
            ->get(['indice_geral', 'snapshotado_em']);

        return view('ngo.conformidade.dashboard', [
            'dashboard' => $dashboard,
            'historico' => $historico,
        ]);
    }

    public function eixo(string $eixo)
    {
        $this->autorizarAdmin();

        $eixosValidos = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao', 'mrosc', 'suas'];
        abort_unless(in_array($eixo, $eixosValidos), 404);

        $tenantId  = auth()->user()->tenant_id;
        $dashboard = $this->service->dashboard($tenantId);

        $ciclo     = $dashboard['ciclos'][$eixo] ?? null;
        $indices   = $dashboard['indices_por_eixo'][$eixo] ?? null;

        $requisitos = RequisitoLegal::with('regra')
            ->where('eixo', $eixo)
            ->where('ativo', true)
            ->get();

        $avaliacoesPorCodigo = collect($dashboard['avaliacoes'])
            ->keyBy('codigo');

        return view('ngo.conformidade.eixo', [
            'eixo'                => $eixo,
            'ciclo'               => $ciclo,
            'indices'             => $indices,
            'requisitos'          => $requisitos,
            'avaliacoesPorCodigo' => $avaliacoesPorCodigo,
        ]);
    }

    public function declarar(Request $request, int $requisito)
    {
        $this->autorizarSoAdmin();

        $req = RequisitoLegal::where('tipo', 'C')->findOrFail($requisito);

        $validated = $request->validate([
            'observacoes' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $ciclo = CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('eixo', $req->eixo)
            ->where('status', 'em_andamento')
            ->firstOrFail();

        AvaliacaoRequisito::create([
            'tenant_id'            => $tenantId,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'   => $req->id,
            'resultado'            => 'verde',
            'avaliado_em'          => now(),
            'avaliado_por'         => auth()->id(),
            'observacoes'          => $validated['observacoes'],
        ]);

        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Declaração registrada com sucesso.');
    }

    public function atualizarCiclo(Request $request)
    {
        $this->autorizarAdmin();

        $validated = $request->validate([
            'eixo'        => ['required', 'string', 'in:cebas_geral,cebas_as,cebas_saude,cebas_educacao,mrosc,suas'],
            'data_inicio' => ['required', 'date'],
            'data_fim'    => ['required', 'date', 'after:data_inicio'],
            'enquadramento' => ['nullable', 'string', 'in:3_anos,5_anos,anual,por_parceria'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('eixo', $validated['eixo'])
            ->where('status', 'em_andamento')
            ->update(['status' => 'encerrado']);

        CicloConformidade::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'status'    => 'em_andamento',
        ]));

        $this->service->invalidarCache($tenantId);
        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Ciclo atualizado.');
    }

    public function recalcular()
    {
        $this->autorizarAdmin();

        $tenantId = auth()->user()->tenant_id;
        $this->service->invalidarCache($tenantId);
        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Recálculo agendado. Atualize a página em alguns instantes.');
    }

    private function autorizarAdmin(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['ngo', 'manager', 'super_admin']),
            403
        );
    }

    private function autorizarSoAdmin(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['ngo', 'super_admin']),
            403
        );
    }
}
