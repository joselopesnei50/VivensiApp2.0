<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Models\Cozinha;
use App\Models\RegistroRefeicao;
use App\Services\RegistroRefeicaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cozinha Solidária — Fase 1.
 *
 * Endpoints JSON do painel: listar cozinhas com realizado vs meta (consolidado
 * pra NGO; própria pro coordenador) e detalhe por cozinha+mês. UI completa
 * fica pra Fase 6 (painel executivo do termo).
 */
class CozinhaPainelController extends Controller
{
    public function __construct(private RegistroRefeicaoService $service)
    {
    }

    /**
     * Lista cozinhas do tenant com realizado vs meta do mês informado
     * (default mês corrente). Para coordenador_cozinha, restringe ao escopo
     * dele via User::cozinhasAtivasIds().
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $mes = $this->mesParam($request);

        $query = Cozinha::query()->ativa();
        if ($user->role === 'coordenador_cozinha') {
            $ids = $user->cozinhasAtivasIds();
            $query->whereIn('id', $ids ?: [-1]); // -1 garante vazio se não tiver
        }

        $cozinhas = $query->get(['id', 'nome', 'meta_refeicoes_mes', 'modalidade_execucao', 'status']);

        $itens = $cozinhas->map(function (Cozinha $c) use ($mes) {
            return [
                'id'                 => $c->id,
                'nome'                => $c->nome,
                'modalidade_execucao' => $c->modalidade_execucao,
                'realizado_vs_meta'   => $this->service->realizadoVsMeta($c, $mes),
            ];
        });

        return response()->json([
            'mes_referencia' => $mes->format('Y-m'),
            'cozinhas'       => $itens,
        ]);
    }

    /**
     * Detalhe de uma cozinha+mês: realizado vs meta, contagem por tipo,
     * número de registros pendentes (não-prestáveis).
     */
    public function detalhe(Request $request, Cozinha $cozinha): JsonResponse
    {
        $this->authorize('view', $cozinha);

        $mes = $this->mesParam($request);
        $rvm = $this->service->realizadoVsMeta($cozinha, $mes);

        $base = RegistroRefeicao::where('cozinha_id', $cozinha->id)
            ->whereYear('data_servico', $mes->year)
            ->whereMonth('data_servico', $mes->month);

        $porTipo = (clone $base)
            ->where('status', RegistroRefeicao::STATUS_VALIDO)
            ->selectRaw('tipo, SUM(quantidade) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $pendentes = (clone $base)
            ->where('status', RegistroRefeicao::STATUS_PENDENTE)
            ->count();

        return response()->json([
            'cozinha'           => [
                'id'  => $cozinha->id,
                'nome' => $cozinha->nome,
                'modalidade_execucao' => $cozinha->modalidade_execucao,
            ],
            'mes_referencia'      => $mes->format('Y-m'),
            'realizado_vs_meta'   => $rvm,
            'realizado_por_tipo' => $porTipo,
            'registros_pendentes' => $pendentes,
        ]);
    }

    private function mesParam(Request $request): Carbon
    {
        $raw = $request->query('mes');
        if ($raw && preg_match('/^\d{4}-\d{2}$/', (string) $raw)) {
            return Carbon::parse($raw . '-01')->startOfMonth();
        }
        return now()->startOfMonth();
    }
}
