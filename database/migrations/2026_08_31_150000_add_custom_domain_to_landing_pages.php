<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom domain pra landing pages (Fase 1 — schema + middleware).
 *
 * Cliente contrata add-on e cadastra seu proprio dominio (raiz e/ou www) que
 * aponta via A/CNAME pro VPS Vivensi. Middleware resolve pelo Host header,
 * fail-closed (dominio nao whitelistado retorna 404, nao vaza tenants).
 *
 * SSL via Let's Encrypt automatizado (Fase 2). Grace de 90d apos cancelar
 * add-on (cert ativo continua servindo ate expirar).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $t) {
            $t->string('custom_domain', 253)->nullable()->unique()->after('slug');
            $t->enum('custom_domain_status', ['pending', 'verifying', 'active', 'failed'])
                ->nullable()
                ->after('custom_domain');
            $t->timestamp('custom_domain_ssl_expires_at')->nullable()->after('custom_domain_status');
            $t->text('custom_domain_error')->nullable()->after('custom_domain_ssl_expires_at');
            $t->index('custom_domain_status');
        });

        Schema::table('tenants', function (Blueprint $t) {
            $t->boolean('custom_domain_addon_active')->default(false)->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $t) {
            $t->dropIndex(['custom_domain_status']);
            $t->dropUnique(['custom_domain']);
            $t->dropColumn([
                'custom_domain',
                'custom_domain_status',
                'custom_domain_ssl_expires_at',
                'custom_domain_error',
            ]);
        });

        Schema::table('tenants', function (Blueprint $t) {
            $t->dropColumn('custom_domain_addon_active');
        });
    }
};
