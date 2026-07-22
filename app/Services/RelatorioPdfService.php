<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AvaliacaoRequisito;
use App\Models\Attendance;
use App\Models\CicloConformidade;
use App\Models\NgoGrant;
use App\Models\ProjectStage;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RelatorioPdfService
{
    public function __construct(private ComplianceCalculationService $compliance) {}

    // ── RMA ──────────────────────────────────────────────────────────────────

    public function dadosRma(int $tenantId, int $mes, int $ano): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim    = $inicio->copy()->endOfMonth();

        $attendances = Attendance::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$inicio->toDateString(), $fim->toDateString()])
            ->get(['id', 'beneficiary_id', 'gratuito', 'tipificacao_suas', 'type', 'date']);

        $total           = $attendances->count();
        $totalGratuito   = $attendances->where('gratuito', true)->count();
        $pctGratuito     = $total > 0 ? round($totalGratuito / $total * 100, 1) : 0.0;
        $beneficiarios   = $attendances->pluck('beneficiary_id')->unique()->filter()->count();

        $porTipificacao = $attendances
            ->groupBy(fn($a) => $a->tipificacao_suas ?? 'Não tipificado')
            ->map(fn($g) => [
                'total'    => $g->count(),
                'gratuito' => $g->where('gratuito', true)->count(),
                'pct'      => $total > 0 ? round($g->count() / $total * 100, 1) : 0.0,
            ])
            ->sortByDesc(fn($v) => $v['total']);

        $porTipo = $attendances
            ->groupBy('type')
            ->map->count();

        return [
            'tenant'          => $tenant,
            'mes'             => $mes,
            'ano'             => $ano,
            'periodo'         => $inicio->translatedFormat('F/Y'),
            'inicio'          => $inicio,
            'fim'             => $fim,
            'total'           => $total,
            'total_gratuito'  => $totalGratuito,
            'pct_gratuito'    => $pctGratuito,
            'beneficiarios'   => $beneficiarios,
            'por_tipificacao' => $porTipificacao,
            'por_tipo'        => $porTipo,
            'gerado_em'       => now()->format('d/m/Y H:i'),
        ];
    }

    // ── Dossiê CEBAS ─────────────────────────────────────────────────────────

    public function dadosCebas(int $tenantId): array
    {
        $tenant    = Tenant::findOrFail($tenantId);
        $dashboard = $this->compliance->dashboard($tenantId);

        $avaliacoes = collect($dashboard['avaliacoes'])->keyBy('codigo');

        $eixosCebas = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao'];
        $requisitos = RequisitoLegal::with('regra')
            ->whereIn('eixo', $eixosCebas)
            ->where('ativo', true)
            ->orderBy('eixo')
            ->orderBy('codigo')
            ->get();

        $ciclos = collect($dashboard['ciclos'])->filter(
            fn($c) => $c && in_array($c->eixo, $eixosCebas)
        );

        $verde    = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'verde' && str_starts_with($a['codigo'], 'CEBAS'))->count();
        $amarelo  = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'amarelo' && str_starts_with($a['codigo'], 'CEBAS'))->count();
        $vermelho = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'vermelho' && str_starts_with($a['codigo'], 'CEBAS'))->count();
        $total    = $verde + $amarelo + $vermelho;
        $indice   = $total > 0 ? round($verde / $total * 100, 1) : 0.0;

        $documentos = Attachment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('tipo_documento')
            ->whereNull('deleted_at')
            ->whereNull('substituido_por_id')
            ->latest()
            ->get(['id', 'original_name', 'tipo_documento', 'valid_until', 'created_at']);

        return [
            'tenant'     => $tenant,
            'requisitos' => $requisitos,
            'avaliacoes' => $avaliacoes,
            'ciclos'     => $ciclos,
            'verde'      => $verde,
            'amarelo'    => $amarelo,
            'vermelho'   => $vermelho,
            'indice'     => $indice,
            'documentos' => $documentos,
            'gerado_em'  => now()->format('d/m/Y H:i'),
        ];
    }

    // ── Relatório MROSC ───────────────────────────────────────────────────────

    public function dadosMrosc(int $tenantId): array
    {
        $tenant    = Tenant::findOrFail($tenantId);
        $dashboard = $this->compliance->dashboard($tenantId);

        $avaliacoes = collect($dashboard['avaliacoes'])->keyBy('codigo');

        $requisitos = RequisitoLegal::with('regra')
            ->where('eixo', 'mrosc')
            ->where('ativo', true)
            ->orderBy('codigo')
            ->get();

        $ciclo = $dashboard['ciclos']['mrosc'] ?? null;

        $verde    = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'verde' && str_starts_with($a['codigo'], 'MROSC'))->count();
        $amarelo  = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'amarelo' && str_starts_with($a['codigo'], 'MROSC'))->count();
        $vermelho = $avaliacoes->filter(fn($a) => ($a['resultado'] ?? '') === 'vermelho' && str_starts_with($a['codigo'], 'MROSC'))->count();
        $total    = $verde + $amarelo + $vermelho;
        $indice   = $total > 0 ? round($verde / $total * 100, 1) : 0.0;

        $grants = NgoGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'active', 'in_progress'])
            ->latest('start_date')
            ->limit(10)
            ->get();

        $transacoesMrosc = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('elegivel_mrosc', true)
            ->selectRaw('type, fonte_recurso, SUM(amount) as total, COUNT(*) as qtd')
            ->groupBy('type', 'fonte_recurso')
            ->get();

        $totalMrosc = $transacoesMrosc->sum('total');

        $etapas = ProjectStage::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('planned_value')
            ->whereNotNull('executed_value')
            ->latest('updated_at')
            ->limit(20)
            ->get();

        return [
            'tenant'          => $tenant,
            'requisitos'      => $requisitos,
            'avaliacoes'      => $avaliacoes,
            'ciclo'           => $ciclo,
            'verde'           => $verde,
            'amarelo'         => $amarelo,
            'vermelho'        => $vermelho,
            'indice'          => $indice,
            'grants'          => $grants,
            'transacoes_mrosc'=> $transacoesMrosc,
            'total_mrosc'     => $totalMrosc,
            'etapas'          => $etapas,
            'gerado_em'       => now()->format('d/m/Y H:i'),
        ];
    }
}
