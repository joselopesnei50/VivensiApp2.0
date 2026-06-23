<?php

namespace App\Services;

use App\Models\Cozinha;
use App\Models\EstornoRefeicao;
use App\Models\Meta;
use App\Models\MetaCronograma;
use App\Models\PeriodoFechado;
use App\Models\RegistroRefeicao;
use App\Models\RegistroRefeicaoFoto;
use App\Models\RegistroRefeicaoPresenca;
use App\Models\TermoColaboracao;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cozinha Solidária — Fase 1.
 *
 * Service do LASTRO. Centraliza a regra de integridade do registro de refeição:
 *  - registrar(): exige foto + geotag dentro do raio + presença. Faltando
 *    qualquer um → status=pendente (não-prestável no realizado vs meta).
 *  - fecharPeriodo(): gera content_hash agregado, registros viram imutáveis
 *    (RegistroRefeicaoObserver bloqueia updates a partir daí).
 *  - estornar(): único caminho de "correção" após fechamento. Modalidade
 *    direta auto-aprova; indireta exige NGO.
 *  - realizadoVsMeta(): cache curto, prioriza Meta + MetaCronograma do Plano
 *    de Trabalho; fallback pra cozinhas.meta_refeicoes_mes.
 */
class RegistroRefeicaoService
{
    public function __construct() {}

    // ── REGISTRAR ────────────────────────────────────────────────────────────

    /**
     * Cria um registro de refeição com fotos e presenças. Valida foto+geotag+
     * presença e o raio do geofence em torno da cozinha. Sem algum desses,
     * grava com status=pendente (não-prestável) — não bloqueia a captura na
     * ponta, mas marca pendência clara.
     *
     * @param array<string,mixed>           $payload      Dados do registro (data_servico, tipo, quantidade, lat, lng, observacao, meta_id).
     * @param array<int,array<string,mixed>> $fotos        [{s3_path, latitude, longitude, captured_at, mime_type, size_bytes}, ...]
     * @param array<int,array<string,mixed>> $presencas    [{beneficiary_id?, nome?, cpf?}, ...]
     */
    public function registrar(
        Cozinha $cozinha,
        array $payload,
        array $fotos,
        array $presencas,
        ?User $registrador = null
    ): RegistroRefeicao {
        return DB::transaction(function () use ($cozinha, $payload, $fotos, $presencas, $registrador) {
            $dataServico = isset($payload['data_servico'])
                ? Carbon::parse($payload['data_servico'])
                : now();

            // Bloqueia criação retroativa em período fechado.
            if ($this->periodoFechado($cozinha, $dataServico)) {
                throw new RuntimeException(
                    'Período já fechado para esta cozinha — abrir estorno em vez de novo registro.'
                );
            }

            $registro = RegistroRefeicao::create([
                'tenant_id'           => $cozinha->tenant_id,
                'cozinha_id'          => $cozinha->id,
                'meta_id'             => $payload['meta_id'] ?? null,
                'data_servico'        => $dataServico->toDateString(),
                'datetime_registrado' => now(), // hora do servidor — anti-fraude
                'tipo'                => $payload['tipo'] ?? RegistroRefeicao::TIPO_OUTRO,
                'quantidade'          => (int) ($payload['quantidade'] ?? 0),
                'latitude'            => $payload['latitude']  ?? null,
                'longitude'           => $payload['longitude'] ?? null,
                'status'              => RegistroRefeicao::STATUS_PENDENTE, // validado adiante
                'registered_by'       => $registrador?->id,
                'observacao'          => $payload['observacao'] ?? null,
            ]);

            foreach ($fotos as $f) {
                RegistroRefeicaoFoto::create(array_merge(['registro_id' => $registro->id], $f));
            }
            foreach ($presencas as $p) {
                RegistroRefeicaoPresenca::create(array_merge(['registro_id' => $registro->id], $p));
            }

            // Decide validez agora — service aplica a regra do lastro.
            $this->reavaliarStatus($registro->fresh(['fotos', 'presencas']));

            $this->invalidarCacheRealizado($cozinha, $dataServico);

            return $registro->fresh(['fotos', 'presencas']);
        });
    }

    /**
     * Reavalia o status do registro: 'valido' se tem foto + geotag dentro do
     * raio + ao menos uma presença; 'pendente' caso contrário.
     */
    public function reavaliarStatus(RegistroRefeicao $registro): RegistroRefeicao
    {
        if ($registro->status === RegistroRefeicao::STATUS_ESTORNADO) {
            return $registro; // estornado é terminal
        }

        $temFoto      = $registro->fotos()->count() > 0;
        $temPresenca  = $registro->presencas()->count() > 0;
        $temGeotag    = $registro->latitude !== null && $registro->longitude !== null;
        $cozinha      = $registro->cozinha;
        $dentroRaio   = $cozinha !== null && $this->dentroDoRaio($cozinha, $registro->latitude, $registro->longitude);

        $novo = ($temFoto && $temPresenca && $temGeotag && $dentroRaio)
            ? RegistroRefeicao::STATUS_VALIDO
            : RegistroRefeicao::STATUS_PENDENTE;

        if ($novo !== $registro->status) {
            // updateQuietly evita disparar o observer de imutabilidade durante
            // a primeira transição pendente→valido logo após criação.
            $registro->updateQuietly(['status' => $novo]);
        }

        return $registro->refresh();
    }

    /**
     * Valida que (latitude, longitude) está dentro do raio configurado em
     * torno da cozinha. Usa fórmula de Haversine.
     */
    public function dentroDoRaio(Cozinha $cozinha, ?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null
            || $cozinha->latitude === null || $cozinha->longitude === null) {
            return false;
        }
        $raio = (int) config('cozinha.geofence_radius_meters', 500);
        return self::haversineMeters(
            (float) $cozinha->latitude, (float) $cozinha->longitude,
            (float) $lat, (float) $lng
        ) <= $raio;
    }

    public static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ── FECHAMENTO ────────────────────────────────────────────────────────────

    /**
     * Fecha um período (cozinha, mês). Só NGO/super_admin (checagem na Policy
     * antes de chamar). A partir daqui, registros do mês ficam imutáveis pelo
     * RegistroRefeicaoObserver (correção só por estorno).
     */
    public function fecharPeriodo(Cozinha $cozinha, DateTimeInterface $mes, User $user): PeriodoFechado
    {
        $mesInicio = Carbon::parse($mes)->startOfMonth();

        $existente = PeriodoFechado::withoutGlobalScopes()
            ->where('cozinha_id', $cozinha->id)
            ->where('mes', $mesInicio->toDateString())
            ->first();
        if ($existente !== null) {
            throw new RuntimeException(sprintf(
                'Período %s já fechado em %s.',
                $mesInicio->format('m/Y'),
                $existente->fechado_em->format('d/m/Y H:i')
            ));
        }

        $registros = RegistroRefeicao::where('cozinha_id', $cozinha->id)
            ->whereYear('data_servico', $mesInicio->year)
            ->whereMonth('data_servico', $mesInicio->month)
            ->orderBy('id')
            ->get();

        $hashes = [];
        $refeicoesTotal = 0;
        foreach ($registros as $r) {
            $payload = sprintf(
                '%d|%s|%s|%d|%s|%s|%s',
                $r->id,
                $r->data_servico->toDateString(),
                $r->tipo,
                $r->quantidade,
                $r->status,
                $r->latitude ?? '',
                $r->longitude ?? ''
            );
            $hash = hash('sha256', $payload);
            $r->updateQuietly(['content_hash' => $hash]);
            $hashes[] = $hash;
            if ($r->isValido()) {
                $refeicoesTotal += $r->quantidade;
            }
        }
        $hashTotal = hash('sha256', implode('|', $hashes));

        $periodo = PeriodoFechado::create([
            'tenant_id'           => $cozinha->tenant_id,
            'cozinha_id'          => $cozinha->id,
            'mes'                 => $mesInicio->toDateString(),
            'fechado_em'          => now(),
            'fechado_por_user_id' => $user->id,
            'content_hash_total'  => $hashTotal,
            'registros_count'     => $registros->count(),
            'refeicoes_total'     => $refeicoesTotal,
        ]);

        $this->invalidarCacheRealizado($cozinha, $mesInicio);

        return $periodo;
    }

    public function periodoFechado(Cozinha $cozinha, DateTimeInterface $data): bool
    {
        $mes = Carbon::parse($data)->startOfMonth()->toDateString();
        return PeriodoFechado::withoutGlobalScopes()
            ->where('cozinha_id', $cozinha->id)
            ->where('mes', $mes)
            ->exists();
    }

    // ── ESTORNO ──────────────────────────────────────────────────────────────

    /**
     * Solicita estorno. Em modalidade DIRETA, o solicitante é a própria
     * gestora e auto-aprova. Em INDIRETA, NGO precisa aprovar via aprovarEstorno().
     */
    public function estornar(
        RegistroRefeicao $registro,
        string $motivo,
        User $solicitante,
        ?string $evidenciaUrl = null
    ): EstornoRefeicao {
        if ($registro->isEstornado()) {
            throw new RuntimeException('Registro já está estornado.');
        }
        $cozinha = $registro->cozinha;
        if ($cozinha === null) {
            throw new RuntimeException('Cozinha do registro não encontrada.');
        }

        return DB::transaction(function () use ($registro, $motivo, $solicitante, $evidenciaUrl, $cozinha) {
            $estorno = EstornoRefeicao::create([
                'tenant_id'              => $registro->tenant_id,
                'registro_id'            => $registro->id,
                'motivo'                 => $motivo,
                'evidencia_url'          => $evidenciaUrl,
                'solicitado_por_user_id' => $solicitante->id,
                'status'                 => EstornoRefeicao::STATUS_PENDENTE,
            ]);

            // Direta = gestora aprova a si própria automaticamente.
            if ($cozinha->modalidade_execucao === Cozinha::MODALIDADE_DIRETA) {
                $this->aprovarEstorno($estorno, $solicitante);
            }

            return $estorno->fresh();
        });
    }

    /**
     * Aprova estorno (NGO/super_admin). Marca o registro como estornado,
     * mesmo se o período já foi fechado (correção auditada).
     */
    public function aprovarEstorno(EstornoRefeicao $estorno, User $aprovador): EstornoRefeicao
    {
        if (!$estorno->isPendente()) {
            return $estorno;
        }

        DB::transaction(function () use ($estorno, $aprovador) {
            $estorno->update([
                'aprovado_por_user_id' => $aprovador->id,
                'aprovado_em'          => now(),
                'status'               => EstornoRefeicao::STATUS_APROVADO,
            ]);

            $registro = $estorno->registro;
            if ($registro !== null) {
                // updateQuietly: bypassa o observer de imutabilidade — esta é a
                // ÚNICA via legítima de mutar registro pós-fechamento.
                $registro->updateQuietly(['status' => RegistroRefeicao::STATUS_ESTORNADO]);
                $this->invalidarCacheRealizado($registro->cozinha, $registro->data_servico);
            }
        });

        return $estorno->fresh();
    }

    public function rejeitarEstorno(EstornoRefeicao $estorno, User $aprovador): EstornoRefeicao
    {
        if (!$estorno->isPendente()) {
            return $estorno;
        }
        $estorno->update([
            'aprovado_por_user_id' => $aprovador->id,
            'aprovado_em'          => now(),
            'status'               => EstornoRefeicao::STATUS_REJEITADO,
        ]);
        return $estorno->fresh();
    }

    // ── REALIZADO VS META ────────────────────────────────────────────────────

    /**
     * Devolve {realizado, meta, percentual, status} pra uma cozinha+mês.
     * Prioriza Meta física do Plano de Trabalho + MetaCronograma; fallback
     * pra cozinhas.meta_refeicoes_mes.
     *
     * Status:
     *  - em_dia: realizado >= 90% da meta
     *  - risco:  60% <= realizado < 90%
     *  - atrasado: realizado < 60%
     *
     * @return array{realizado:int, meta:int, percentual:float, status:string, fonte_meta:string}
     */
    public function realizadoVsMeta(Cozinha $cozinha, DateTimeInterface $mes): array
    {
        $mesInicio = Carbon::parse($mes)->startOfMonth();
        $ttl = (int) config('cozinha.realizado_cache_ttl', 300);
        $key = sprintf('cozinha.realizado.%d.%s', $cozinha->id, $mesInicio->format('Y-m'));

        return Cache::remember($key, $ttl, function () use ($cozinha, $mesInicio) {
            $realizado = (int) RegistroRefeicao::where('cozinha_id', $cozinha->id)
                ->where('status', RegistroRefeicao::STATUS_VALIDO)
                ->whereYear('data_servico', $mesInicio->year)
                ->whereMonth('data_servico', $mesInicio->month)
                ->sum('quantidade');

            [$meta, $fonte] = $this->resolverMetaDoMes($cozinha, $mesInicio);
            $percentual = $meta > 0 ? round(($realizado / $meta) * 100, 2) : 0.0;
            $status = $meta === 0
                ? 'sem_meta'
                : ($percentual >= 90 ? 'em_dia' : ($percentual >= 60 ? 'risco' : 'atrasado'));

            return [
                'realizado'  => $realizado,
                'meta'       => $meta,
                'percentual' => $percentual,
                'status'     => $status,
                'fonte_meta' => $fonte,
            ];
        });
    }

    /**
     * Resolve a meta de refeições do mês. Ordem:
     *   1. MetaCronograma associada a Meta física do Plano de Trabalho do
     *      termo desta cozinha, para o mês.
     *   2. cozinhas.meta_refeicoes_mes (fallback operacional simples).
     *
     * @return array{0:int,1:string}
     */
    private function resolverMetaDoMes(Cozinha $cozinha, Carbon $mesInicio): array
    {
        try {
            $termo = $cozinha->termo;
            if ($termo !== null) {
                $plano = $termo->planoVigenteEm($mesInicio);
                if ($plano !== null) {
                    $metaFisica = Meta::where('plano_trabalho_id', $plano->id)
                        ->where('tipo', Meta::TIPO_FISICA)
                        ->first();
                    if ($metaFisica !== null) {
                        $cronograma = MetaCronograma::where('meta_id', $metaFisica->id)
                            ->whereDate('mes', $mesInicio->toDateString())
                            ->first();
                        if ($cronograma !== null) {
                            return [(int) $cronograma->quantidade, 'plano_trabalho'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RegistroRefeicaoService: falha ao resolver meta do plano', [
                'cozinha_id' => $cozinha->id,
                'mes'        => $mesInicio->toDateString(),
                'error'      => $e->getMessage(),
            ]);
        }
        return [(int) $cozinha->meta_refeicoes_mes, 'cozinha_operacional'];
    }

    private function invalidarCacheRealizado(?Cozinha $cozinha, DateTimeInterface $dataOuMes): void
    {
        if ($cozinha === null) {
            return;
        }
        $mes = Carbon::parse($dataOuMes)->startOfMonth();
        Cache::forget(sprintf('cozinha.realizado.%d.%s', $cozinha->id, $mes->format('Y-m')));
    }
}
