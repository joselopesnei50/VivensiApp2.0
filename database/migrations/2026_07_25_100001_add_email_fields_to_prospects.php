<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campos de e-mail à tabela prospects para permitir:
     *  - Scraping automático do site do prospect (email_source='scraped')
     *  - Input manual do usuário (email_source='manual')
     *  - Envio via campanha de e-mail marketing (Brevo) apenas quando
     *    email_opt_in=true (LGPD art. 7º).
     */
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('email', 190)->nullable()->after('phone');
            $table->boolean('email_opt_in')->default(false)->after('email');
            $table->string('email_source', 20)->nullable()->after('email_opt_in');
            $table->timestamp('email_found_at')->nullable()->after('email_source');

            $table->index(['tenant_id', 'email_opt_in'], 'idx_prospects_tenant_optin');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropIndex('idx_prospects_tenant_optin');
            $table->dropColumn(['email', 'email_opt_in', 'email_source', 'email_found_at']);
        });
    }
};
