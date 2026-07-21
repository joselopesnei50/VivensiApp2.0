<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequisitoLegal;
use App\Models\RegraAvaliacao;

class RegraAvaliacaoSeeder extends Seeder
{
    public function run(): void
    {
        $regras = [
            // ── Tipo A — Calculáveis ─────────────────────────────────────────
            'CEBAS-G-008' => [
                'modelo'                    => 'Transaction',
                'campo_filtro'              => null,
                'formula'                   => 'cobertura_mensal',
                'threshold'                 => 100.00,
                'threshold_tipo'            => 'minimo',
                'unidade'                   => '%_meses',
                'threshold_editavel_admin'  => true,
                'threshold_editavel_tenant' => false,
            ],
            'CEBAS-AS-002' => [
                'modelo'                    => 'Attendance',
                'campo_filtro'              => 'gratuito',
                'formula'                   => 'percentual',
                'threshold'                 => 20.00,  // % mínimo — editável pelo super admin sem redeploy
                'threshold_tipo'            => 'minimo',
                'unidade'                   => '%',
                'threshold_editavel_admin'  => true,
                'threshold_editavel_tenant' => false,
            ],
            'MROSC-P-006' => [
                'modelo'                    => 'Transaction',
                'campo_filtro'              => 'elegivel_mrosc',
                'formula'                   => 'cobertura_documento',
                'threshold'                 => 100.00,
                'threshold_tipo'            => 'minimo',
                'unidade'                   => '%',
                'threshold_editavel_admin'  => false,
                'threshold_editavel_tenant' => false,
            ],
            'MROSC-P-007' => [
                'modelo'                    => 'ProjectStage',
                'campo_filtro'              => 'executed_value',
                'formula'                   => 'desvio_meta',
                'threshold'                 => 25.00,  // desvio máximo permitido (%)
                'threshold_tipo'            => 'maximo',
                'unidade'                   => '%_desvio',
                'threshold_editavel_admin'  => true,
                'threshold_editavel_tenant' => false,
            ],
            'SUAS-OP-002' => [
                'modelo'                    => 'Attendance',
                'campo_filtro'              => 'tipificacao_suas',
                'formula'                   => 'preenchimento',
                'threshold'                 => 80.00,  // % de atendimentos com tipificação
                'threshold_tipo'            => 'minimo',
                'unidade'                   => '%',
                'threshold_editavel_admin'  => true,
                'threshold_editavel_tenant' => false,
            ],
            'SUAS-OP-003' => [
                'modelo'                    => 'Employee',
                'campo_filtro'              => 'categoria_profissional',
                'formula'                   => 'equipe_referencia',
                'threshold'                 => 1.00,   // mínimo 1 assistente social ativo
                'threshold_tipo'            => 'minimo',
                'unidade'                   => 'registros',
                'threshold_editavel_admin'  => false,
                'threshold_editavel_tenant' => false,
            ],

            // ── Tipo B — Documentais ─────────────────────────────────────────
            'CEBAS-G-001' => [
                'tipo_documento_obrigatorio' => 'estatuto',
                'alerta_dias_antes'          => 0, // não vence — alerta se ausente
            ],
            'CEBAS-G-003' => [
                'tipo_documento_obrigatorio' => 'cnd_inss',
                'alerta_dias_antes'          => 30,
            ],
            'CEBAS-G-004' => [
                'tipo_documento_obrigatorio' => 'crf_fgts',
                'alerta_dias_antes'          => 30,
            ],
            'CEBAS-G-005' => [
                'tipo_documento_obrigatorio' => 'cnd_federal',
                'alerta_dias_antes'          => 30,
            ],
            'CEBAS-G-006' => [
                'tipo_documento_obrigatorio' => 'cnd_estadual',
                'alerta_dias_antes'          => 30,
            ],
            'CEBAS-G-007' => [
                'tipo_documento_obrigatorio' => 'cnd_municipal',
                'alerta_dias_antes'          => 30,
            ],
            'CEBAS-AS-001' => [
                'tipo_documento_obrigatorio' => 'cnas_inscricao',
                'alerta_dias_antes'          => 60,
            ],
            'CEBAS-AS-003' => [
                'tipo_documento_obrigatorio' => 'cmas_inscricao',
                'alerta_dias_antes'          => 60,
            ],
            'CEBAS-AS-004' => [
                'tipo_documento_obrigatorio' => 'cneas_registro',
                'alerta_dias_antes'          => 60,
            ],
            'CEBAS-AS-006' => [
                'tipo_documento_obrigatorio' => 'balanco_patrimonial',
                'alerta_dias_antes'          => 0,
            ],
            'CEBAS-AS-007' => [
                'tipo_documento_obrigatorio' => 'parecer_auditoria',
                'alerta_dias_antes'          => 0,
            ],
            'CEBAS-S-002' => [
                'tipo_documento_obrigatorio' => 'licenca_sanitaria',
                'alerta_dias_antes'          => 60,
            ],
            'MROSC-P-001' => [
                'tipo_documento_obrigatorio' => 'plano_trabalho',
                'alerta_dias_antes'          => 0,
            ],
            'MROSC-P-005' => [
                'tipo_documento_obrigatorio' => 'prestacao_contas',
                'alerta_dias_antes'          => 30,
            ],

            // ── Tipo C — Declaratórios ────────────────────────────────────────
            'CEBAS-G-002' => [
                'pergunta_declaracao' => 'Confirmo que o CNPJ desta entidade está ativo e regular junto à Receita Federal, sem pendências de baixa ou suspensão.',
                'requer_anexo'        => false,
            ],
            'CEBAS-AS-005' => [
                'pergunta_declaracao' => 'Confirmo que o relatório de atividades do exercício foi elaborado, está disponível para consulta e descreve os serviços prestados, público atendido e resultados alcançados.',
                'requer_anexo'        => true,
            ],
            'CEBAS-S-001' => [
                'pergunta_declaracao' => 'Declaro que pelo menos 60% dos atendimentos realizados neste exercício foram prestados ao Sistema Único de Saúde (SUS), conforme comprovantes em nossos registros.',
                'requer_anexo'        => true,
            ],
            'CEBAS-E-001' => [
                'pergunta_declaracao' => 'Declaro que foram concedidas bolsas de estudo na proporção mínima de 1 bolsa integral para cada 5 alunos pagantes no exercício vigente.',
                'requer_anexo'        => true,
            ],
            'MROSC-P-002' => [
                'pergunta_declaracao' => 'Confirmo que foi aberta conta bancária exclusiva para movimentação dos recursos desta parceria, e que nenhum recurso do convênio foi movimentado em conta compartilhada.',
                'requer_anexo'        => false,
            ],
            'MROSC-P-003' => [
                'pergunta_declaracao' => 'Confirmo que o relatório de execução (objeto e financeiro) referente ao período foi encaminhado ao órgão parceiro dentro do prazo estabelecido.',
                'requer_anexo'        => true,
            ],
            'MROSC-P-004' => [
                'pergunta_declaracao' => 'Confirmo que o extrato do instrumento e os relatórios parciais desta parceria foram publicados no portal de transparência do órgão concedente conforme exigido pelo art. 11 da Lei 13.019/2014.',
                'requer_anexo'        => false,
            ],
            'SUAS-OP-001' => [
                'pergunta_declaracao' => 'Confirmo que o Registro Mensal de Atendimentos (RMA) do mês de referência foi encaminhado ao órgão gestor do SUAS até o prazo regulamentar.',
                'requer_anexo'        => false,
            ],
            'SUAS-OP-004' => [
                'pergunta_declaracao' => 'Confirmo que o Plano de Ação desta entidade para o exercício vigente foi elaborado e submetido à aprovação do Conselho Municipal de Assistência Social (CMAS).',
                'requer_anexo'        => true,
            ],
            'SUAS-OP-005' => [
                'pergunta_declaracao' => 'Confirmo que a entidade se submeteu ao processo de monitoramento e avaliação pelo CMAS no exercício vigente e que as recomendações foram recebidas e estão sendo implementadas.',
                'requer_anexo'        => false,
            ],
        ];

        foreach ($regras as $codigo => $dados) {
            $requisito = RequisitoLegal::where('codigo', $codigo)->first();

            if (! $requisito) {
                $this->command->warn("Requisito {$codigo} não encontrado — execute RequisitoLegalSeeder antes.");
                continue;
            }

            RegraAvaliacao::updateOrCreate(
                ['requisito_legal_id' => $requisito->id],
                $dados
            );
        }
    }
}
