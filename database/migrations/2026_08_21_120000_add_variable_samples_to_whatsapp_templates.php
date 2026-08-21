<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amostras das variaveis do template (Meta exige 'example.body_text' quando
 * o corpo usa {{1}}, {{2}}, etc — sem isso o template e rejeitado com
 * "Variaveis de modelo sem texto de amostra").
 *
 * Persistir permite: reenvio pos-rejeicao, clonagem entre instancias, e
 * que o operador nao precise redigitar toda vez.
 *
 * Formato: { "1": "Instituto Caminhos", "2": "Araraquara" }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->json('variable_samples')->nullable()->after('components');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('variable_samples');
        });
    }
};
