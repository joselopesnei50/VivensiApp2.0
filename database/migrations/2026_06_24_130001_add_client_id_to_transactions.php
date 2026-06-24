<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vivensi — Módulo MEI / Recibos.
 *
 * Vincula Transaction (type=income) ao Client do MEI, espelhando o pattern
 * de ngo_donor_id já existente. NULLABLE — Transaction continua válida sem
 * client (recibo avulso permitido), e mantém retrocompat com fluxos NGO/
 * Gestor que não usam esse campo.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }
        if (Schema::hasColumn('transactions', 'client_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('ngo_donor_id')
                ->constrained('clients')->nullOnDelete();
            $table->index(['tenant_id', 'client_id']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transactions') || !Schema::hasColumn('transactions', 'client_id')) {
            return;
        }
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['tenant_id', 'client_id']);
            $table->dropColumn('client_id');
        });
    }
};
