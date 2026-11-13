<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cota diária de e-mail marketing: 25 por organização (decisão do Nei, 13/11/2026).
 * Quem ainda está no padrão antigo (50) passa a 25; cotas ajustadas à mão pelo
 * Super Admin (qualquer outro valor) ficam como estão.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'daily_email_quota')) {
            return;
        }
        DB::table('tenants')->where('daily_email_quota', 50)->update(['daily_email_quota' => 25]);
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tenants ALTER COLUMN daily_email_quota SET DEFAULT 25');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tenants', 'daily_email_quota')) {
            return;
        }
        DB::table('tenants')->where('daily_email_quota', 25)->update(['daily_email_quota' => 50]);
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tenants ALTER COLUMN daily_email_quota SET DEFAULT 50');
        }
    }
};
