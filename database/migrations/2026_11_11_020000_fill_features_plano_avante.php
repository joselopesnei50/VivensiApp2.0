<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Preenche "Recursos Inclusos" do plano Terceiro Setor - Avante (R$ 349,90).
 * Acha o plano pelo produto da AbacatePay ou pelo nome; só grava se ainda estiver vazio,
 * para nunca sobrescrever o que foi editado no painel.
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
        $plans = DB::table('subscription_plans')
            ->where('target_audience', 'ngo')
            ->where(function ($q) {
                $q->where('abacatepay_product_id', 'prod_qDSXkday2ZMPAugkYBHKcJJf')
                  ->orWhere('name', 'like', '%Avante%');
            })
            ->get(['id', 'features']);

        foreach ($plans as $plan) {
            $current = json_decode((string) $plan->features, true);
            if (!empty(array_filter((array) $current))) {
                continue; // já tem recursos cadastrados
            }
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'features'   => json_encode(self::FEATURES, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Não desfaz: o conteúdo pode ter sido editado depois no painel.
    }
};
