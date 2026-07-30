<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix crítico de segurança 2026-07-30:
 * Impedir que dois tenants tenham o mesmo phone_number_id.
 *
 * Sem esse índice, o webhook lookup em ProcessCloudApiWebhook fazia
 * WhatsappInstance::where('phone_number_id', $x)->first() retornar a
 * primeira row encontrada — se por qualquer bug de onboarding dois
 * tenants tivessem o mesmo número, mensagens de um caiam na caixa do
 * outro. Vazamento de dados entre clientes.
 *
 * completeManualSignup já bloqueava o hijack, mas completeSignup
 * (Embedded Signup) não. Índice único no DB fecha o buraco em qualquer
 * caminho, presente ou futuro.
 */
return new class extends Migration {
    public function up(): void
    {
        // Detecta duplicatas antes de aplicar o índice. Se existir, aborta
        // com mensagem clara pra o operador decidir (o script não escolhe
        // qual tenant "vence").
        $duplicates = DB::table('whatsapp_instances')
            ->select('phone_number_id')
            ->whereNotNull('phone_number_id')
            ->groupBy('phone_number_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone_number_id');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Migration abortada: existem phone_number_id duplicados em whatsapp_instances: '
                . $duplicates->implode(', ')
                . '. Resolva o conflito manualmente (decidir qual tenant fica com o número) antes de aplicar o índice único.'
            );
        }

        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->unique('phone_number_id', 'whatsapp_instances_phone_number_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropUnique('whatsapp_instances_phone_number_id_unique');
        });
    }
};
