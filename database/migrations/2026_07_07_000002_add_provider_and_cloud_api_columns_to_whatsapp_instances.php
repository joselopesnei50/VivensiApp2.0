<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna `provider` + credenciais Meta Cloud API à `whatsapp_instances`.
 *
 * `provider` distingue a origem do serviço de envio:
 *   - 'evolution' (default, retrocompatível): Evolution API auto-hospedada
 *   - 'cloud_api' : Meta WhatsApp Business Cloud API (Tech Provider)
 *
 * As colunas Cloud API são nullable — só populadas quando provider='cloud_api'.
 * O `graph_access_token` é armazenado cifrado (AES-256) via mutator no model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->string('provider', 20)->default('evolution')->after('tenant_id')->index();
            $table->string('waba_id')->nullable()->after('provider')->index();
            $table->string('phone_number_id')->nullable()->after('waba_id')->index();
            $table->text('graph_access_token')->nullable()->after('phone_number_id');
        });

        // Backfill defensivo (todos os registros existentes viram Evolution)
        \Illuminate\Support\Facades\DB::table('whatsapp_instances')
            ->whereNull('provider')
            ->orWhere('provider', '')
            ->update(['provider' => 'evolution']);
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropIndex(['provider']);
            $table->dropIndex(['waba_id']);
            $table->dropIndex(['phone_number_id']);
            $table->dropColumn(['provider', 'waba_id', 'phone_number_id', 'graph_access_token']);
        });
    }
};
