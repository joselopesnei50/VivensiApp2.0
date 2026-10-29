<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_campaigns', 'members_optin_implicit')) {
                // Quando audience_type=groups + group_send_mode=members: trata o fato
                // de o contato estar no grupo como consentimento (membership = opt-in).
                // Default true para que o comportamento esperado pelo cliente (campanhas
                // em grupos privados/corporativos) funcione sem bloqueios surpresa.
                // opt_out, blacklist e blocked_at continuam sendo respeitados (direitos LGPD).
                $table->boolean('members_optin_implicit')->default(true)->after('group_send_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('broadcast_campaigns', 'members_optin_implicit')) {
                $table->dropColumn('members_optin_implicit');
            }
        });
    }
};
