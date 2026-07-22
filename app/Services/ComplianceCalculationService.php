<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AvaliacaoRequisito;
use App\Models\CicloConformidade;
use App\Models\Employee;
use App\Models\Evidencia;
use App\Models\ProjectStage;
use App\Models\Attendance;
use App\Models\RequisitoLegal;
use App\Models\RegraAvaliacao;
use App\Models\SnapshotConformidade;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplianceCalculationService
{
    // ── Chaves de cache ───────────────────────────────────────────────────────

    private function cacheKey(int $tenantId): string
    {
        return "compliance.dashboard.{$tenantId}." . now()->format('Y-m-d');
    }

    public function invalidarCache(int $tenantId): void
    {
        Cache::forget($this->cacheKey($tenantId));
    }

    // ── Ponto de entrada principal ────────────────────────────────────────────

    /**
     * Calcula ou recupera do cache o índice de conformidade completo do tenant.
     * Retorna array pronto para a view do dashboard.
     */
    public function dashboard(int $tenantId): array
    {
        return Cache::remember($this->cacheKey($tenantId), 3600, function () use ($tenantId) {
            return $this->calcularDashboard($tenantId);
        });
    }

    public function calcularDashboard(int $tenantId): array
    {
        $eixos     = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao', 'mrosc', 'suas'];
        $requisitos = RequisitoLegal::with('regra')->where('ativo', true)->get();

        // Garante ciclos abertos para cada eixo-família
        $ciclos = $this->garantirCiclos($tenantId);

        $avaliacoes  = [];
        $pendencias  = [];
        $indicesPorEixo = [];

        foreach ($eixos as $eixo) {
            $ciclo = $ciclos[$eixo] ?? null;
            if (! $ciclo) continue;

            $reqsDoEixo = $requisitos->where('eixo', $eixo);
            $resultados = [];

            foreach ($reqsDoEixo as $req) {
                try {
                    $aval = $this->avaliarRequisito($tenantId, $req, $ciclo);
                    $resultados[] = $aval;
                    $avaliacoes[] = $aval;

                    if (in_array($aval['resultado'], ['vermelho', 'amarelo'])) {
                        $pendencias[] = array_merge($aval, [
                            'eixo'  => $eixo,
                            'risco' => $req->risco,
                            'tipo'  => $req->tipo,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("ComplianceCalculation: erro no requisito {$req->codigo}", ['error' => $e->getMessage()]);
                }
            }

            $indicesPorEixo[$eixo] = $this->agregarIndice($resultados);
        }

        // Índice geral = média dos eixos com pelo menos 1 requisito avaliado
        $indicesValidos = array_filter($indicesPorEixo, fn($v) => $v['total'] > 0);
        $indiceGeral = count($indicesValidos) > 0
            ? round(array_sum(array_column($indicesValidos, 'percentual')) / count($indicesValidos), 1)
            : 0;

        // Ordena pendências: vermelho > amarelo, alto > médio > baixo
        usort($pendencias, function ($a, $b) {
            $resultadoOrd = ['vermelho' => 0, 'amarelo' => 1];
            $riscoOrd     = ['alto' => 0, 'medio' => 1, 'baixo' => 2];
            $rA = ($resultadoOrd[$a['resultado']] ?? 9) * 10 + ($riscoOrd[$a['risco']] ?? 9);
            $rB = ($resultadoOrd[$b['resultado']] ?? 9) * 10 + ($riscoOrd[$b['risco']] ?? 9);
            return $rA <=> $rB;
        });

        return [
            'indice_geral'     => $indiceGeral,
            'indices_por_eixo' => $indicesPorEixo,
            'ciclos'           => $ciclos,
            'pendencias'       => $pendencias,
            'avaliacoes'       => $avaliacoes,
            'calculado_em'     => now()->toDateTimeString(),
        ];
    }

    // ── Avaliação por tipo ────────────────────────────────────────────────────

    public function avaliarRequisito(int $tenantId, RequisitoLegal $req, CicloConformidade $ciclo): array
    {
        $regra = $req->regra;

        return match ($req->tipo) {
            'A' => $this->avaliarTipoA($tenantId, $req, $regra, $ciclo),
            'B' => $this->avaliarTipoB($tenantId, $req, $regra),
            'C' => $this->avaliarTipoC($tenantId, $req, $ciclo),
            default => $this->semAvaliacao($req),
        };
    }

    private function avaliarTipoA(int $tenantId, RequisitoLegal $req, ?RegraAvaliacao $regra, CicloConformidade $ciclo): array
    {
        if (! $regra || ! $regra->formula) {
            return $this->semAvaliacao($req);
        }

        $valor = $this->calcularTipoA($tenantId, $req->codigo, $regra, $ciclo);

        // Threshold: ciclo pode ter override, senão usa o da regra
        $threshold = $ciclo->thresholdOverride($req->codigo) ?? (float) $regra->threshold;

        $resultado = $this->determinarResultado($valor, $threshold, $regra->threshold_tipo ?? 'minimo');

        return [
            'codigo'          => $req->codigo,
            'titulo'          => $req->titulo,
            'resultado'       => $resultado,
            'valor_calculado' => $valor,
            'threshold'       => $threshold,
            'unidade'         => $regra->unidade,
            'avaliado_por'    => null,
        ];
    }

    private function avaliarTipoB(int $tenantId, RequisitoLegal $req, ?RegraAvaliacao $regra): array
    {
        if (! $regra || ! $regra->tipo_documento_obrigatorio) {
            return $this->semAvaliacao($req);
        }

        $doc = Attachment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('tipo_documento', $regra->tipo_documento_obrigatorio)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $doc) {
            return [
                'codigo'          => $req->codigo,
                'titulo'          => $req->titulo,
                'resultado'       => 'vermelho',
                'valor_calculado' => null,
                'threshold'       => null,
                'unidade'         => null,
                'avaliado_por'    => null,
                'detalhe'         => 'Documento não encontrado',
            ];
        }

        // Sem validade → assumir válido (estatuto, por exemplo)
        if (! $doc->valid_until) {
            return $this->resultadoB($req, 'verde', $doc, 'Documento presente');
        }

        $diasRestantes = (int) now()->diffInDays($doc->valid_until, false);
        $alertaDias    = $regra->alerta_dias_antes ?? 30;

        if ($diasRestantes < 0) {
            return $this->resultadoB($req, 'vermelho', $doc, "Vencido há " . abs($diasRestantes) . " dias");
        }
        if ($diasRestantes <= $alertaDias) {
            return $this->resultadoB($req, 'amarelo', $doc, "Vence em {$diasRestantes} dias");
        }

        return $this->resultadoB($req, 'verde', $doc, "Válido até " . $doc->valid_until->format('d/m/Y'));
    }

    private function resultadoB(RequisitoLegal $req, string $resultado, Attachment $doc, string $detalhe): array
    {
        return [
            'codigo'          => $req->codigo,
            'titulo'          => $req->titulo,
            'resultado'       => $resultado,
            'valor_calculado' => null,
            'threshold'       => null,
            'unidade'         => null,
            'avaliado_por'    => null,
            'detalhe'         => $detalhe,
            'attachment_id'   => $doc->id,
        ];
    }

    private function avaliarTipoC(int $tenantId, RequisitoLegal $req, CicloConformidade $ciclo): array
    {
        $declaracao = AvaliacaoRequisito::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('ciclo_conformidade_id', $ciclo->id)
            ->where('requisito_legal_id', $req->id)
            ->whereNotNull('avaliado_por')
            ->latest('avaliado_em')
            ->first();

        $resultado = $declaracao ? 'verde' : 'vermelho';
        $detalhe   = $declaracao
            ? "Declarado em " . $declaracao->avaliado_em->format('d/m/Y')
            : 'Aguardando declaração';

        return [
            'codigo'          => $req->codigo,
            'titulo'          => $req->titulo,
            'resultado'       => $resultado,
            'valor_calculado' => null,
            'threshold'       => null,
            'unidade'         => null,
            'avaliado_por'    => $declaracao?->avaliado_por,
            'detalhe'         => $detalhe,
        ];
    }

    private function semAvaliacao(RequisitoLegal $req): array
    {
        return [
            'codigo'          => $req->codigo,
            'titulo'          => $req->titulo,
            'resultado'       => 'nao_aplicavel',
            'valor_calculado' => null,
            'threshold'       => null,
            'unidade'         => null,
            'avaliado_por'    => null,
        ];
    }

    // ── Fórmulas Tipo A ───────────────────────────────────────────────────────

    public function calcularTipoA(int $tenantId, string $codigo, RegraAvaliacao $regra, CicloConformidade $ciclo): float
    {
        return match ($regra->formula) {
            'percentual'       => $this->percentual($tenantId, $regra, $ciclo),
            'preenchimento'    => $this->preenchimento($tenantId, $regra, $ciclo),
            'cobertura_mensal' => $this->coberturaMensal($tenantId, $ciclo),
            'cobertura_documento' => $this->coberturaDocumento($tenantId),
            'desvio_meta'      => $this->desvioMeta($tenantId, $regra),
            'equipe_referencia' => $this->equipeReferencia($tenantId, $regra),
            default            => 0.0,
        };
    }

    /** CEBAS-AS-002: % de atendimentos com gratuito=true */
    private function percentual(int $tenantId, RegraAvaliacao $regra, CicloConformidade $ciclo): float
    {
        $q = Attendance::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereBetween('date', [$ciclo->data_inicio, $ciclo->data_fim]);

        $total     = (int) $q->count();
        $filtrados = (int) (clone $q)->where($regra->campo_filtro, true)->count();

        return $total > 0 ? round($filtrados / $total * 100, 2) : 0.0;
    }

    /** SUAS-OP-002: % de atendimentos com tipificacao_suas preenchida */
    private function preenchimento(int $tenantId, RegraAvaliacao $regra, CicloConformidade $ciclo): float
    {
        $q = Attendance::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereBetween('date', [$ciclo->data_inicio, $ciclo->data_fim]);

        $total      = (int) $q->count();
        $preenchidos = (int) (clone $q)->whereNotNull($regra->campo_filtro)->count();

        return $total > 0 ? round($preenchidos / $total * 100, 2) : 0.0;
    }

    /** CEBAS-G-008: % de meses do ciclo com pelo menos 1 lançamento */
    private function coberturaMensal(int $tenantId, CicloConformidade $ciclo): float
    {
        $mesesComLancamento = (int) Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$ciclo->data_inicio, $ciclo->data_fim])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as mes")
            ->distinct()
            ->get()
            ->count();

        $totalMeses = max(1, (int) $ciclo->data_inicio->diffInMonths($ciclo->data_fim) + 1);

        return round($mesesComLancamento / $totalMeses * 100, 2);
    }

    /** MROSC-P-006: % de transações elegíveis com comprovante (attachment) */
    private function coberturaDocumento(int $tenantId): float
    {
        $elegiveis = (int) Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('elegivel_mrosc', true)
            ->count();

        if ($elegiveis === 0) return 100.0; // sem elegíveis = OK

        $comAnexo = (int) Attachment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('attachable_type', Transaction::class)
            ->whereNull('deleted_at')
            ->distinct('attachable_id')
            ->count('attachable_id');

        return round($comAnexo / $elegiveis * 100, 2);
    }

    /** MROSC-P-007: % de metas com desvio dentro do threshold */
    private function desvioMeta(int $tenantId, RegraAvaliacao $regra): float
    {
        $stages = ProjectStage::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('executed_value')
            ->where('planned_value', '>', 0)
            ->get();

        if ($stages->isEmpty()) return 100.0; // sem metas = OK

        $threshold = (float) $regra->threshold; // ex: 25.0%
        $cumpridas = $stages->filter(function ($s) use ($threshold) {
            $desvio = abs((float)$s->planned_value - (float)$s->executed_value) / (float)$s->planned_value * 100;
            return $desvio <= $threshold;
        })->count();

        return round($cumpridas / $stages->count() * 100, 2);
    }

    /** SUAS-OP-003: qtd de assistentes sociais ativos */
    private function equipeReferencia(int $tenantId, RegraAvaliacao $regra): float
    {
        return (float) Employee::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('categoria_profissional', 'assistente_social')
            ->count();
    }

    // ── Determinação de resultado ─────────────────────────────────────────────

    private function determinarResultado(float $valor, float $threshold, string $tipo): string
    {
        if ($tipo === 'maximo') {
            // Ex: desvio máximo 25% → se valor calculado é o % de metas cumpridas
            // Valor calculado aqui já é % de metas dentro do limite (ver desvioMeta)
            if ($valor >= 100)          return 'verde';
            if ($valor >= $threshold)   return 'amarelo';
            return 'vermelho';
        }

        // minimo (padrão)
        if ($valor >= $threshold)                  return 'verde';
        if ($valor >= ($threshold * 0.8))          return 'amarelo';
        return 'vermelho';
    }

    // ── Agregação ─────────────────────────────────────────────────────────────

    private function agregarIndice(array $resultados): array
    {
        $total      = count($resultados);
        $verde      = count(array_filter($resultados, fn($r) => $r['resultado'] === 'verde'));
        $amarelo    = count(array_filter($resultados, fn($r) => $r['resultado'] === 'amarelo'));
        $vermelho   = count(array_filter($resultados, fn($r) => $r['resultado'] === 'vermelho'));
        $naoAplica  = count(array_filter($resultados, fn($r) => $r['resultado'] === 'nao_aplicavel'));
        $avaliados  = $total - $naoAplica;
        $percentual = $avaliados > 0 ? round($verde / $avaliados * 100, 1) : 0;

        return compact('total', 'verde', 'amarelo', 'vermelho', 'naoAplica', 'avaliados', 'percentual');
    }

    public function calcularIndice(int $tenantId, CicloConformidade $ciclo): array
    {
        $requisitos = RequisitoLegal::with('regra')
            ->where('ativo', true)
            ->where('eixo', $ciclo->eixo)
            ->get();

        $resultados = [];
        foreach ($requisitos as $req) {
            try {
                $resultados[] = $this->avaliarRequisito($tenantId, $req, $ciclo);
            } catch (\Throwable) {}
        }

        return $this->agregarIndice($resultados);
    }

    // ── Persistência de avaliações Tipo A ─────────────────────────────────────

    public function persistirAvaliacoesTipoA(int $tenantId): void
    {
        $ciclos    = CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'em_andamento')
            ->get();

        $requisitos = RequisitoLegal::with('regra')
            ->where('ativo', true)
            ->where('tipo', 'A')
            ->get();

        foreach ($ciclos as $ciclo) {
            foreach ($requisitos as $req) {
                if ($req->eixo !== $ciclo->eixo && ! str_starts_with($req->eixo, 'cebas_')) continue;

                try {
                    $avaliacao = $this->avaliarRequisito($tenantId, $req, $ciclo);

                    AvaliacaoRequisito::withoutGlobalScopes()->updateOrCreate(
                        [
                            'tenant_id'            => $tenantId,
                            'ciclo_conformidade_id' => $ciclo->id,
                            'requisito_legal_id'   => $req->id,
                            'avaliado_por'         => null, // só sobrescreve avaliações do sistema
                        ],
                        [
                            'resultado'       => $avaliacao['resultado'],
                            'valor_calculado' => $avaliacao['valor_calculado'],
                            'avaliado_em'     => now(),
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning("ComplianceCalculation: falha ao persistir {$req->codigo} tenant {$tenantId}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    // ── Snapshot ──────────────────────────────────────────────────────────────

    public function gerarSnapshot(int $tenantId): void
    {
        $dashboard = $this->calcularDashboard($tenantId);
        $ciclos    = $dashboard['ciclos'];

        foreach ($ciclos as $eixo => $ciclo) {
            $indices = $dashboard['indices_por_eixo'][$eixo] ?? null;
            if (! $indices) continue;

            SnapshotConformidade::create([
                'tenant_id'            => $tenantId,
                'ciclo_conformidade_id' => $ciclo->id,
                'indice_geral'         => $dashboard['indice_geral'],
                'indice_cebas'         => $this->indiceDoGrupo('cebas', $dashboard['indices_por_eixo']),
                'indice_suas'          => $dashboard['indices_por_eixo']['suas']['percentual'] ?? 0,
                'indice_mrosc'         => $dashboard['indices_por_eixo']['mrosc']['percentual'] ?? 0,
                'total_verde'          => $indices['verde'],
                'total_amarelo'        => $indices['amarelo'],
                'total_vermelho'       => $indices['vermelho'],
                'total_nao_aplicavel'  => $indices['naoAplica'],
                'snapshotado_em'       => now(),
            ]);

            // Só um snapshot por ciclo-família
            break;
        }
    }

    private function indiceDoGrupo(string $grupo, array $indicesPorEixo): float
    {
        $eixosDoGrupo = array_filter($indicesPorEixo, fn($v, $k) => str_starts_with($k, $grupo), ARRAY_FILTER_USE_BOTH);
        $validos = array_filter($eixosDoGrupo, fn($v) => $v['total'] > 0);
        if (empty($validos)) return 0.0;
        return round(array_sum(array_column($validos, 'percentual')) / count($validos), 1);
    }

    // ── Ciclos ────────────────────────────────────────────────────────────────

    /**
     * Garante que existe um CicloConformidade em_andamento para cada eixo.
     * Se não existir, cria um padrão de 3 anos a partir de hoje.
     */
    private function garantirCiclos(int $tenantId): array
    {
        $eixos  = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao', 'mrosc', 'suas'];
        $ciclos = [];

        foreach ($eixos as $eixo) {
            $ciclo = CicloConformidade::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('eixo', $eixo)
                ->where('status', 'em_andamento')
                ->first();

            if (! $ciclo) {
                $ciclo = CicloConformidade::create([
                    'tenant_id'     => $tenantId,
                    'eixo'          => $eixo,
                    'data_inicio'   => now()->startOfYear(),
                    'data_fim'      => now()->startOfYear()->addYears(3),
                    'enquadramento' => '3_anos',
                    'status'        => 'em_andamento',
                ]);
            }

            $ciclos[$eixo] = $ciclo;
        }

        return $ciclos;
    }
}
