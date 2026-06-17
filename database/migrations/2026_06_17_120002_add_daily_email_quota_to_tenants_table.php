<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cota diária de e-mails por tenant (Fase 2 — item 3.3 do roadmap).
     * Default 50 e-mails/dia. Conta apenas campanhas (Brevo bulk); e-mails
     * transacionais (2FA, reset de senha, alertas críticos) continuam fora
     * da contagem.
     *
     * Migration aditiva — só adiciona coluna com default. Tenants existentes
     * herdam 50 automaticamente; Super Admin pode contratar capacidade extra
     * editando o campo no perfil do tenant.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'daily_email_quota')) {
                $table->unsignedInteger('daily_email_quota')
                    ->default(50)
                    ->after('subscription_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'daily_email_quota')) {
                $table->dropColumn('daily_email_quota');
            }
        });
    }
};
