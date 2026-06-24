<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vivensi — Módulo MEI / Anexar NFS-e.
 *
 * Opção C da pesquisa NFS-e (docs/sprint-mei): em vez de integrar com SEFIN
 * Nacional ou pagar provedor SaaS, deixamos o MEI emitir a nota pelo portal
 * gratuito do governo (nfse.gov.br) e ARMAZENAMOS A REFERÊNCIA aqui — número
 * + data + PDF. Vivensi vira "guarda-tudo" do dossiê fiscal sem custo recorrente.
 *
 * Campos aditivos, todos nullable — retrocompat com fluxos que não usam.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'nfse_numero')) {
                $table->string('nfse_numero', 60)->nullable()->after('receipt_path');
            }
            if (!Schema::hasColumn('transactions', 'nfse_url_pdf')) {
                $table->string('nfse_url_pdf', 500)->nullable()->after('nfse_numero');
            }
            if (!Schema::hasColumn('transactions', 'nfse_emitida_em')) {
                $table->date('nfse_emitida_em')->nullable()->after('nfse_url_pdf');
            }
        });

        if (Schema::hasColumn('transactions', 'nfse_numero')) {
            // index separado pra evitar IF na callback principal acima
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->index(['tenant_id', 'nfse_emitida_em']);
                });
            } catch (\Throwable) {
                // ignora se já existe (idempotência)
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }
        Schema::table('transactions', function (Blueprint $table) {
            try { $table->dropIndex(['tenant_id', 'nfse_emitida_em']); } catch (\Throwable) {}
            if (Schema::hasColumn('transactions', 'nfse_emitida_em')) $table->dropColumn('nfse_emitida_em');
            if (Schema::hasColumn('transactions', 'nfse_url_pdf'))    $table->dropColumn('nfse_url_pdf');
            if (Schema::hasColumn('transactions', 'nfse_numero'))     $table->dropColumn('nfse_numero');
        });
    }
};
