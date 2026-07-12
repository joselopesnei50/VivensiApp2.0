<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LGPD art. 15 (eliminação) + art. 18 (portabilidade/exportação) self-service.
 *
 * Ate agora a tabela suportava so admin criar requests. Adiciona campos pra
 * usuario final: token de download seguro do ZIP de exportação + prazo de
 * carencia (grace period) na deletação com possibilidade de cancelar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lgpd_data_requests', function (Blueprint $table) {
            // Deletion — grace period de 30 dias antes do purge definitivo
            $table->timestamp('scheduled_for')->nullable()->after('processed_at')
                ->comment('Deletion agendado para esta data (grace 30d). Null = nao agendada.');
            $table->timestamp('cancelled_at')->nullable()->after('scheduled_for')
                ->comment('Usuario cancelou dentro do grace period. Null = ativa.');

            // Export — token unico p/ download seguro do ZIP
            $table->string('export_token', 64)->nullable()->unique()->after('cancelled_at')
                ->comment('Token opaco (256 bits) enviado por email p/ baixar ZIP');
            $table->timestamp('export_expires_at')->nullable()->after('export_token')
                ->comment('Token de download expira em 48h apos gerado');
            $table->string('export_file_path', 500)->nullable()->after('export_expires_at')
                ->comment('Path relativo em storage/app privado — nunca em public/');
            $table->unsignedInteger('export_download_count')->default(0)->after('export_file_path')
                ->comment('Quantas vezes foi baixado (auditoria)');

            $table->index(['type', 'status', 'scheduled_for'], 'lgpd_requests_purge_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lgpd_data_requests', function (Blueprint $table) {
            $table->dropIndex('lgpd_requests_purge_idx');
            $table->dropColumn([
                'scheduled_for',
                'cancelled_at',
                'export_token',
                'export_expires_at',
                'export_file_path',
                'export_download_count',
            ]);
        });
    }
};
