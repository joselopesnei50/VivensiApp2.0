<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona suporte a envio via template Cloud API (Meta) no /whatsapp/broadcast.
 *
 * `send_channel` distingue o pipeline (Evolution texto/imagem/audio livre vs
 * template aprovado da Cloud API). Default 'evolution' preserva 100% das
 * campanhas existentes.
 *
 * `template_id` referencia whatsapp_templates.id (nullable — obrigatorio apenas
 * quando send_channel = 'cloud_api_template').
 *
 * `template_variables` guarda valores FIXOS por campanha (v1: mesmo valor pra
 * todos destinatarios). Formato: {"1":"Instituto Caminhos","2":"Vivensi"}.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->string('send_channel')->default('evolution')->after('audience_type');
            $table->unsignedBigInteger('template_id')->nullable()->after('send_channel');
            $table->json('template_variables')->nullable()->after('template_id');

            $table->foreign('template_id')
                ->references('id')->on('whatsapp_templates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['send_channel', 'template_id', 'template_variables']);
        });
    }
};
