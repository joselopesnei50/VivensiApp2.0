<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona 'contact_list' ao enum audience_type de email_campaigns.
 * MySQL: ALTER TABLE. SQLite (tests): recria via DBAL/temporary — usamos
 * approach compativel: change column pra string (mais permissivo). Enforcement
 * do valor eh feito no controller via validation `in:...`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none','donors','donors_optins','contact_list') NOT NULL DEFAULT 'tenant_admins'");
        } else {
            // SQLite (tests) e Postgres: relaxa pra string. Controller valida via `in:`.
            Schema::table('email_campaigns', function ($table) {
                $table->string('audience_type', 30)->default('tenant_admins')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none','donors','donors_optins') NOT NULL DEFAULT 'tenant_admins'");
        }
    }
};
