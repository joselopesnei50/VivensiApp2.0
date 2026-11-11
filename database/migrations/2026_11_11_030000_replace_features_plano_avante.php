<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Substitui "Recursos Inclusos" do plano Terceiro Setor - Avante pela lista nova
 * (pedido do dono em 2026-11-11). A migração anterior não gravava porque o plano
 * já tinha itens. Acha o plano pelo produto AbacatePay ou pelo nome "Avante".
 */
return new class extends Migration
{
    private const FEATURES = [
        'Beneficiários e famílias, com histórico de atendimentos',
        'Turmas e oficinas com lista de frequência',
        'Termo de consentimento LGPD (com responsável para menores)',
        'Financeiro, orçamento e conciliação bancária',
        'Prestação de contas e relatórios em PDF',
        'Portal da transparência e Serviço de Informação ao Cidadão',
        'Projetos, editais e captação de recursos',
        'Doadores, apadrinhamentos e recibos de doação',
        'Equipe, RH e voluntários com certificado',
        'Campanhas de e-mail e atendimento por WhatsApp',
        'Landing page com o domínio da sua ONG',
        'Rifas online com PIX',
        'Assistente de IA (Bruce)',
    ];

    public function up(): void
    {
        DB::table('subscription_plans')
            ->where('target_audience', 'ngo')
            ->where(function ($q) {
                $q->where('abacatepay_product_id', 'prod_qDSXkday2ZMPAugkYBHKcJJf')
                  ->orWhere('name', 'like', '%Avante%');
            })
            ->update([
                'features'   => json_encode(self::FEATURES, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
    }
};
