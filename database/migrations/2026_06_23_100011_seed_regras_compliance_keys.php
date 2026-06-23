<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (seed inicial de chaves).
 *
 * Cria as CHAVES de regras esperadas pelo módulo, mas SEM VALORES (parametros
 * = null, curado_por_user_id = null). O RegrasComplianceService se recusa a
 * aplicar regra não-curada — trava por design (R1 do guardião).
 *
 * Quando a curadoria jurídica humana cadastrar o valor + portaria, basta
 * UPDATE direto na tabela (ou tela de admin). Isso evita chumbar números
 * errados na pressa do lançamento.
 *
 * Idempotente: não cria duplicata se a chave já existir.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('regras_compliance')) {
            return;
        }

        $chaves = [
            [
                'chave'        => 'taxa_administracao_teto',
                'fonte_legal'  => 'PENDENTE — curadoria jurídica deve preencher (Lei 13.019/2014 e portaria vigente).',
            ],
            [
                'chave'        => 'vedacao_mao_obra_percentual',
                'fonte_legal'  => 'PENDENTE — curadoria jurídica (Decreto 11.937/2024 e portarias do ciclo vigente).',
            ],
            [
                'chave'        => 'classificacao_custeio_capital',
                'fonte_legal'  => 'PENDENTE — curadoria jurídica (Lei 14.628/2023, MROSC).',
            ],
            [
                'chave'        => 'prazo_prestacao_contas_dias',
                'fonte_legal'  => 'PENDENTE — curadoria jurídica (MROSC Lei 13.019/2014 art. 69).',
            ],
            [
                'chave'        => 'limites_taxa_obtv',
                'fonte_legal'  => 'PENDENTE — curadoria jurídica (Portaria MDS vigente).',
            ],
        ];

        $now = now();
        foreach ($chaves as $reg) {
            $existe = DB::table('regras_compliance')->where('chave', $reg['chave'])->exists();
            if ($existe) {
                continue;
            }

            DB::table('regras_compliance')->insert([
                'chave'                 => $reg['chave'],
                'parametros'            => null,
                'fonte_legal'           => $reg['fonte_legal'],
                'vigencia_inicio'       => $now->toDateString(),
                'vigencia_fim'          => null,
                'aplica_a_modalidade'   => 'ambas',
                'curado_por_user_id'    => null,
                'curado_em'             => null,
                'created_by_user_id'    => null,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('regras_compliance')) {
            return;
        }
        DB::table('regras_compliance')
            ->whereIn('chave', [
                'taxa_administracao_teto',
                'vedacao_mao_obra_percentual',
                'classificacao_custeio_capital',
                'prazo_prestacao_contas_dias',
                'limites_taxa_obtv',
            ])
            ->whereNull('curado_em')
            ->delete();
    }
};
