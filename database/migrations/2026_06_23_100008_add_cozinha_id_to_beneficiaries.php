<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 8/9).
 *
 * Vincula Beneficiary à Cozinha. Coluna NULLABLE — NGO comum (sem Cozinha)
 * continua funcionando sem alteração (decisão §3.2 / R2 do guardião).
 *
 * FK com nullOnDelete: se a cozinha for removida, o beneficiário fica órfão
 * mas vivo — não cascateia perda de dado humano.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('beneficiaries')) {
            return;
        }
        if (Schema::hasColumn('beneficiaries', 'cozinha_id')) {
            return;
        }

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('cozinha_id')->nullable()->after('tenant_id')
                ->constrained('cozinhas')->nullOnDelete();
            $table->index(['tenant_id', 'cozinha_id']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('beneficiaries')) {
            return;
        }
        if (!Schema::hasColumn('beneficiaries', 'cozinha_id')) {
            return;
        }

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropForeign(['cozinha_id']);
            $table->dropIndex(['tenant_id', 'cozinha_id']);
            $table->dropColumn('cozinha_id');
        });
    }
};
