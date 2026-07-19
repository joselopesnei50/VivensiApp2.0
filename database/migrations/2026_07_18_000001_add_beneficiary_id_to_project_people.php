<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga project_people <-> beneficiaries de forma opt-in.
 *
 * Ate agora ProjectPerson (cadastro dentro do projeto) e Beneficiary (cadastro
 * central da ONG, com PII criptografada) eram paralelos. Sem migracao
 * retroativa por nome (evita match errado). Vinculos passam a ser opt-in
 * pelo form de cadastro de pessoa no projeto.
 *
 * Comportamento no delete:
 * - Excluir Beneficiary -> nullify beneficiary_id no ProjectPerson (preserva
 *   historico da chamada da turma sem manter FK orfa)
 * - Excluir ProjectPerson -> nao mexe no Beneficiary
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            $table->foreignId('beneficiary_id')
                ->nullable()
                ->after('project_id')
                ->constrained('beneficiaries')
                ->nullOnDelete();
            $table->index(['tenant_id', 'beneficiary_id'], 'project_people_tenant_benef_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            $table->dropIndex('project_people_tenant_benef_idx');
            $table->dropForeign(['beneficiary_id']);
            $table->dropColumn('beneficiary_id');
        });
    }
};
