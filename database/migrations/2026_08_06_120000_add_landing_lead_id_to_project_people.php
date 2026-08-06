<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link explicito ProjectPerson -> LandingPageLead (2026-08-06).
 *
 * Antes: enrollFromLanding criava ProjectPerson mas nao guardava a
 * referencia do lead que originou. Isso quebrava a exibicao dos
 * custom_fields no modal Ver detalhes (dependia de match por telefone,
 * fragil quando formatacao varia ou quando o cadastro foi manual).
 *
 * Agora o FK aponta pro lead original — modal traz custom_fields
 * direto. onDelete=setNull pra manter a Person se o lead for expurgado
 * por LGPD.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            $table->unsignedBigInteger('landing_lead_id')->nullable()->after('beneficiary_id');
            $table->foreign('landing_lead_id')
                  ->references('id')->on('landing_page_leads')
                  ->nullOnDelete();
            $table->index('landing_lead_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            $table->dropForeign(['landing_lead_id']);
            $table->dropIndex(['landing_lead_id']);
            $table->dropColumn('landing_lead_id');
        });
    }
};
