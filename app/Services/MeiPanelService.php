<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\Transaction;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Vivensi — Módulo MEI.
 *
 * Calcula as 3 métricas-vendedoras do MVP MEI:
 *  - tetoMei(): soma receitas do ano corrente, devolve {realizado_centavos,
 *    teto_centavos, percentual, status, faltam_centavos}.
 *  - proximoDas(): data do próximo vencimento + valor + se já foi pago no
 *    mês corrente (busca Transaction expense com description marker).
 *  - dreMensal(): receita - despesa - DAS = lucro líquido do mês.
 *
 * Tudo em CENTAVOS (int) pra evitar float drift. View formata pra reais.
 *
 * Nenhum schema novo — usa Transaction existente (type/status/date/description).
 */
class MeiPanelService
{
    private const CACHE_PREFIX = 'mei.panel';

    // ── Termômetro do Teto ────────────────────────────────────────────────

    /**
     * @return array{realizado_centavos:int, teto_centavos:int, percentual:float, status:string, faltam_centavos:int, ano:int}
     */
    public function tetoMei(int $tenantId, ?int $ano = null): array
    {
        $ano = $ano ?? (int) now()->year;
        $ttl = (int) config('mei.cache_ttl', 300);
        $key = sprintf('%s.teto.%d.%d', self::CACHE_PREFIX, $tenantId, $ano);

        return Cache::remember($key, $ttl, function () use ($tenantId, $ano) {
            $teto = (int) config('mei.teto_anual_centavos', 8_100_000);

            $realizadoReais = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')
                ->where('status', 'paid')
                ->whereYear('date', $ano)
                ->sum('amount');
            $realizadoCent = (int) round($realizadoReais * 100);

            $percentual = $teto > 0 ? round(($realizadoCent / $teto) * 100, 1) : 0.0;
            $verdeMax    = (int) config('mei.termometro.verde_max', 70);
            $amareloMax  = (int) config('mei.termometro.amarelo_max', 90);

            $status = $percentual >= $amareloMax
                ? 'vermelho'
                : ($percentual >= $verdeMax ? 'amarelo' : 'verde');

            return [
                'realizado_centavos' => $realizadoCent,
                'teto_centavos'      => $teto,
                'percentual'         => $percentual,
                'status'             => $status,
                'faltam_centavos'    => max(0, $teto - $realizadoCent),
                'ano'                => $ano,
            ];
        });
    }

    // ── Lembrete DAS ──────────────────────────────────────────────────────

    /**
     * @return array{vencimento:string, dias_restantes:int, valor_centavos:int, pago:bool, transaction_id:?int, descricao_marker:string}
     */
    public function proximoDas(int $tenantId, ?DateTimeInterface $hoje = null): array
    {
        $hoje = $hoje ? Carbon::parse($hoje) : Carbon::now();
        $dia  = (int) config('mei.das.dia_vencimento', 20);
        $valor = (int) config('mei.das.valor_centavos', 8_190);
        $marker = (string) config('mei.das.description_marker', 'DAS — MEI');

        // Próximo vencimento: dia 20 do mês corrente se ainda não passou,
        // senão dia 20 do próximo mês.
        $vencimentoEsteMes = $hoje->copy()->day($dia);
        $vencimento = $hoje->lte($vencimentoEsteMes)
            ? $vencimentoEsteMes
            : $vencimentoEsteMes->copy()->addMonth();

        // Pago = transaction expense com description marcador NO MÊS CORRENTE.
        // Importante: quando o dia atual passa do vencimento (ex: hoje 24, DAS
        // venceu dia 20), o $vencimento aponta pro PRÓXIMO mês — mas o pagamento
        // do DAS atrasado é feito no mês corrente. Por isso filtramos por
        // $hoje->year/month, não por $vencimento->year/month.
        $pago = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->where('description', $marker)
            ->whereYear('date', $hoje->year)
            ->whereMonth('date', $hoje->month)
            ->first();

        return [
            'vencimento'      => $vencimento->toDateString(),
            'dias_restantes'  => (int) $hoje->copy()->startOfDay()->diffInDays($vencimento->startOfDay(), false),
            'valor_centavos'  => $valor,
            'pago'            => $pago !== null,
            'transaction_id'  => $pago?->id,
            'descricao_marker'=> $marker,
        ];
    }

    /**
     * Marca o DAS do mês como pago criando uma Transaction expense com o
     * description marker. Idempotente: se já existe DAS pago no mês, devolve
     * a transação existente.
     */
    public function marcarDasPago(int $tenantId, ?DateTimeInterface $data = null): Transaction
    {
        $data = $data ? Carbon::parse($data) : Carbon::now();
        $marker = (string) config('mei.das.description_marker', 'DAS — MEI');
        $valor = (float) config('mei.das.valor_centavos', 8_190) / 100;

        $existente = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->where('description', $marker)
            ->whereYear('date', $data->year)
            ->whereMonth('date', $data->month)
            ->first();
        if ($existente !== null) {
            return $existente;
        }

        $categoria = $this->resolverCategoriaDas($tenantId);

        $tx = Transaction::create([
            'tenant_id'    => $tenantId,
            'description'  => $marker,
            'amount'       => $valor,
            'type'         => 'expense',
            'status'       => 'paid',
            'date'         => $data->toDateString(),
            'paid_at'      => now(),
            'category_id'  => $categoria?->id,
        ]);

        $this->invalidarCache($tenantId, $data->year, $data->month);
        return $tx;
    }

    private function resolverCategoriaDas(int $tenantId): ?FinancialCategory
    {
        $nome = 'Impostos / DAS MEI';
        try {
            $cat = FinancialCategory::where('tenant_id', $tenantId)
                ->where('name', $nome)
                ->first();
            if ($cat !== null) {
                return $cat;
            }
            return FinancialCategory::create([
                'tenant_id' => $tenantId,
                'name'      => $nome,
                'type'      => 'expense',
            ]);
        } catch (\Throwable) {
            // FinancialCategory model pode ter restrições específicas que
            // mudam por tenant — falha silenciosa, transaction roda sem categoria.
            return null;
        }
    }

    // ── DRE mensal ────────────────────────────────────────────────────────

    /**
     * @return array{receita_centavos:int, despesa_centavos:int, das_centavos:int, lucro_centavos:int, mes:string}
     */
    public function dreMensal(int $tenantId, ?int $ano = null, ?int $mes = null): array
    {
        $ano = $ano ?? (int) now()->year;
        $mes = $mes ?? (int) now()->month;
        $ttl = (int) config('mei.cache_ttl', 300);
        $key = sprintf('%s.dre.%d.%d-%02d', self::CACHE_PREFIX, $tenantId, $ano, $mes);

        return Cache::remember($key, $ttl, function () use ($tenantId, $ano, $mes) {
            $marker = (string) config('mei.das.description_marker', 'DAS — MEI');

            $receita = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')->where('status', 'paid')
                ->whereYear('date', $ano)->whereMonth('date', $mes)
                ->sum('amount');

            // Despesa exclui o DAS — DAS sai como linha separada na DRE.
            $despesa = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')->where('status', 'paid')
                ->whereYear('date', $ano)->whereMonth('date', $mes)
                ->where('description', '!=', $marker)
                ->sum('amount');

            $das = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')->where('status', 'paid')
                ->whereYear('date', $ano)->whereMonth('date', $mes)
                ->where('description', $marker)
                ->sum('amount');

            $receitaC = (int) round($receita * 100);
            $despesaC = (int) round($despesa * 100);
            $dasC     = (int) round($das * 100);

            return [
                'receita_centavos'  => $receitaC,
                'despesa_centavos'  => $despesaC,
                'das_centavos'      => $dasC,
                'lucro_centavos'    => $receitaC - $despesaC - $dasC,
                'mes'               => sprintf('%04d-%02d', $ano, $mes),
            ];
        });
    }

    private function invalidarCache(int $tenantId, int $ano, int $mes): void
    {
        Cache::forget(sprintf('%s.teto.%d.%d', self::CACHE_PREFIX, $tenantId, $ano));
        Cache::forget(sprintf('%s.dre.%d.%d-%02d', self::CACHE_PREFIX, $tenantId, $ano, $mes));
    }

    /**
     * Cobertura de NFS-e nas receitas pagas do ano-corrente.
     * Conta quantas Transactions income/paid têm nfse_numero preenchido.
     *
     * @return array{total_receitas:int, com_nfse:int, sem_nfse:int, percentual:float}
     */
    public function coberturaNfse(int $tenantId, ?int $ano = null): array
    {
        $ano = $ano ?? (int) now()->year;

        $base = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereYear('date', $ano);

        $total = (int) (clone $base)->count();
        $comNfse = (int) (clone $base)->whereNotNull('nfse_numero')->count();

        return [
            'total_receitas' => $total,
            'com_nfse'       => $comNfse,
            'sem_nfse'       => max(0, $total - $comNfse),
            'percentual'     => $total > 0 ? round(($comNfse / $total) * 100, 1) : 0.0,
        ];
    }
}
