<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Auditoria 2026-08-29 P3.b.1 (IDOR baixa) — VolunteerCertificate ganha
 * tenant_id + backfill via join com volunteers. Habilita BelongsToTenant
 * no model, fechando a possibilidade de download cross-tenant por
 * colisao de volunteer_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('volunteer_certificates', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
        });

        // Backfill: certificado herda tenant do voluntario. Se voluntario
        // foi apagado (FK loose), tenant_id fica null — cai no fail-closed
        // do BelongsToTenant e nao vaza pra ninguem.
        // Sintaxe portavel (subquery) — funciona em MySQL e SQLite (tests).
        DB::statement('
            UPDATE volunteer_certificates
            SET tenant_id = (
                SELECT tenant_id FROM volunteers
                WHERE volunteers.id = volunteer_certificates.volunteer_id
            )
            WHERE tenant_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('volunteer_certificates', function (Blueprint $t) {
            $t->dropIndex(['tenant_id']);
            $t->dropColumn('tenant_id');
        });
    }
};
