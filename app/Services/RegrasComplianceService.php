<?php

namespace App\Services;

use App\Models\RegraCompliance;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cozinha Solidária — Fase 0.
 *
 * Coração do compliance. Resolve qual regra está vigente para uma chave +
 * data + modalidade (direta/indireta). NUNCA inventa valor: regras vêm da
 * tabela `regras_compliance`. Se a regra não foi curada por jurídico
 * (curado_em null OU parametros null), o service se RECUSA a aplicar e
 * lança RuntimeException com mensagem clara — falha cedo é mais seguro
 * do que aplicar regra errada e devolver recurso depois (R1 do guardião).
 *
 * Cache curto (5 min, Redis) pra refletir ajuste jurídico rápido. Observer
 * (RegraComplianceObserver) invalida cache em qualquer alteração.
 */
class RegrasComplianceService
{
    public const CACHE_TTL_SECONDS = 300;
    public const CACHE_PREFIX      = 'regras_compliance';

    /**
     * Resolve a regra vigente para (chave, data, modalidade?). Retorna os
     * parâmetros já parseados (JSON). Lança RuntimeException se:
     *  - não houver nenhuma regra cadastrada com essa chave/data
     *  - a regra vigente não foi curada (parametros null OU curado_em null)
     *
     * @return array<string,mixed>
     */
    public function resolverRegra(
        string $chave,
        DateTimeInterface $data,
        ?string $modalidade = null
    ): array {
        $regra = $this->resolverModelo($chave, $data, $modalidade);

        if ($regra === null) {
            throw new RuntimeException(sprintf(
                "Regra de compliance '%s' não encontrada para %s%s. " .
                "Nenhum cadastro vigente — abrir ticket jurídico.",
                $chave,
                $data->format('Y-m-d'),
                $modalidade ? " (modalidade {$modalidade})" : ''
            ));
        }

        if (!$regra->isCurada()) {
            throw new RuntimeException(sprintf(
                "Regra '%s' não foi curada por jurídico (parametros vazios ou " .
                "curadoria pendente). Fonte legal cadastrada: %s. " .
                "Abrir ticket de curadoria antes de prosseguir.",
                $chave,
                $regra->fonte_legal
            ));
        }

        return (array) $regra->parametros;
    }

    /**
     * Valida um contexto contra uma regra. Retorna estrutura padronizada com
     * resultado + fonte legal. Não lança em violação — devolve `['ok'=>false]`
     * pra UI mostrar mensagem amigável citando a portaria.
     *
     * Cada chave tem seu próprio comparador (extensível). Quando a chave não
     * tem comparador implementado, o service ainda devolve os parâmetros para
     * o caller decidir.
     *
     * @param array<string,mixed> $contexto
     * @return array{ok:bool,violacao:?string,fonte_legal:string,parametros:array<string,mixed>}
     */
    public function validar(
        string $chave,
        DateTimeInterface $data,
        array $contexto,
        ?string $modalidade = null
    ): array {
        $regra = $this->resolverModelo($chave, $data, $modalidade);
        if ($regra === null || !$regra->isCurada()) {
            // resolverRegra() lança a exceção descritiva — reaproveita.
            $this->resolverRegra($chave, $data, $modalidade);
        }

        $parametros = (array) $regra->parametros;
        $violacao   = $this->compararContexto($chave, $parametros, $contexto);

        return [
            'ok'          => $violacao === null,
            'violacao'    => $violacao,
            'fonte_legal' => $regra->fonte_legal,
            'parametros'  => $parametros,
        ];
    }

    /**
     * Marca a regra como curada por jurídico. Sem isso, resolverRegra/validar
     * recusam aplicar. Idempotente: re-curar atualiza o registro de curadoria.
     */
    public function registrarCuradoria(
        RegraCompliance $regra,
        User $curador,
        array $parametros,
        string $fonteLegal
    ): RegraCompliance {
        $regra->update([
            'parametros'         => $parametros,
            'fonte_legal'        => $fonteLegal,
            'curado_por_user_id' => $curador->id,
            'curado_em'          => now(),
        ]);

        // R4 do guardião: registra na audit log central. O método record()
        // já tem try/catch interno, então não bloqueia a curadoria.
        \App\Models\AdminAuditLog::record('regra_compliance_curada', [
            'target_type'       => 'regra_compliance',
            'target_id'         => $regra->id,
            'target_name'       => $regra->chave,
            'chave'             => $regra->chave,
            'fonte_legal'       => $fonteLegal,
            'aplica_modalidade' => $regra->aplica_a_modalidade,
        ]);

        $this->invalidarCache($regra->chave);

        return $regra;
    }

    /**
     * Invalida cache local desta chave. Chamado pelo observer também.
     */
    public function invalidarCache(string $chave): void
    {
        // Pra simplicidade, usamos uma versão da chave que é incrementada a
        // cada alteração. Evita varrer todas as combinações de data/modalidade.
        Cache::forget(self::cacheVersionKey($chave));
    }

    private function resolverModelo(
        string $chave,
        DateTimeInterface $data,
        ?string $modalidade
    ): ?RegraCompliance {
        $version = Cache::rememberForever(self::cacheVersionKey($chave), fn () => 1);
        $cacheKey = sprintf(
            '%s.v%d.%s.%s.%s',
            self::CACHE_PREFIX,
            $version,
            $chave,
            $data->format('Y-m-d'),
            $modalidade ?? 'any'
        );

        $id = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($chave, $data, $modalidade) {
                $query = RegraCompliance::query()
                    ->where('chave', $chave)
                    ->vigenteEm($data)
                    ->orderByDesc('vigencia_inicio');

                if ($modalidade !== null) {
                    $query->aplicaA($modalidade);
                }
                return optional($query->first())->id;
            }
        );

        return $id ? RegraCompliance::find($id) : null;
    }

    /**
     * Comparador por chave. Devolve string com motivo de violação ou null se OK.
     *
     * @param array<string,mixed> $parametros
     * @param array<string,mixed> $contexto
     */
    private function compararContexto(string $chave, array $parametros, array $contexto): ?string
    {
        switch ($chave) {
            case 'taxa_administracao_teto':
                // parametros: {"percentual": 0.15}; contexto: {"percentual_aplicado": 0.18}
                $teto = (float) ($parametros['percentual'] ?? 0.0);
                $aplicado = (float) ($contexto['percentual_aplicado'] ?? 0.0);
                if ($teto > 0 && $aplicado > $teto + 1e-9) {
                    return sprintf(
                        'Taxa de administração aplicada (%.2f%%) acima do teto (%.2f%%).',
                        $aplicado * 100,
                        $teto * 100
                    );
                }
                return null;

            case 'prazo_prestacao_contas_dias':
                // parametros: {"dias": 90}; contexto: {"dias_decorridos": 95}
                $limite    = (int) ($parametros['dias'] ?? 0);
                $decorrido = (int) ($contexto['dias_decorridos'] ?? 0);
                if ($limite > 0 && $decorrido > $limite) {
                    return sprintf(
                        'Prazo de prestação de contas estourado: %d dias decorridos, limite de %d.',
                        $decorrido,
                        $limite
                    );
                }
                return null;

            default:
                // Sem comparador específico — caller decide pelos parametros.
                return null;
        }
    }

    private static function cacheVersionKey(string $chave): string
    {
        return self::CACHE_PREFIX . '.version.' . $chave;
    }
}
