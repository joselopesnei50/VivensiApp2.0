<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opção C: landing vinculada a Projeto.
 *
 * Quando target_project_id está preenchido, o submitLead público além de
 * gravar o lead do CRM também cria ProjectPerson (matriculado no projeto)
 * e, se target_link_beneficiary=true, cria/vincula Beneficiary usando
 * blind index de CPF (idempotente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('target_project_id')->nullable()->after('status');
            $table->boolean('target_creates_person')->default(false)->after('target_project_id');
            $table->boolean('target_link_beneficiary')->default(false)->after('target_creates_person');

            $table->foreign('target_project_id')
                ->references('id')->on('projects')
                ->nullOnDelete();

            $table->index('target_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['target_project_id']);
            $table->dropIndex(['target_project_id']);
            $table->dropColumn(['target_project_id', 'target_creates_person', 'target_link_beneficiary']);
        });
    }
};
