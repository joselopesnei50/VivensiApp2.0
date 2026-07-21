<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('tipo_documento')->nullable()->after('mime_type');
            // estatuto|ata_assembleia|cnd_inss|crf_fgts|cnd_federal|cnd_estadual|cnd_municipal
            // |cnas_inscricao|cmas_inscricao|cneas_registro|parecer_auditoria|balanco_patrimonial
            // |dre|relatorio_atividades|plano_trabalho|prestacao_contas|contrato_trabalho|outros
            $table->date('valid_until')->nullable()->after('tipo_documento');
            $table->unsignedInteger('versao')->default(1)->after('valid_until');
            $table->unsignedBigInteger('substituido_por_id')->nullable()->after('versao');
            $table->timestamp('alerta_enviado_em')->nullable()->after('substituido_por_id');

            $table->index(['tipo_documento', 'valid_until'], 'idx_attachment_tipo_validade');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex('idx_attachment_tipo_validade');
            $table->dropColumn(['tipo_documento', 'valid_until', 'versao', 'substituido_por_id', 'alerta_enviado_em']);
        });
    }
};
