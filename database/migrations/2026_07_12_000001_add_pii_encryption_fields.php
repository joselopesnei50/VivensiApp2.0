<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C4 — Encryption at-rest para PII em Lead e User (LGPD art. 46).
 *
 * Adiciona blind index (HMAC-SHA256 do plaintext) para permitir busca em
 * colunas cifradas. Padrão idêntico ao Beneficiary.cpf_bidx / nis_bidx.
 *
 * Colunas de plaintext (email, phone) mudam para TEXT porque Crypt::encryptString
 * gera output de ~200+ bytes mesmo para input curto (envelope + base64 + IV).
 * VARCHAR(200) atual do email nao cabe o cipher.
 *
 * Backfill dos dados existentes: comando `pii:backfill-encryption` (idempotente).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Passo 1: remove indice existente em (tenant_id, email) antes de mudar coluna
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_tenant_id_email_index');
        });

        // Passo 2: altera colunas para TEXT (aceita output do Crypt::encryptString)
        Schema::table('leads', function (Blueprint $table) {
            $table->text('email')->nullable()->change();
            $table->text('phone')->nullable()->change();
        });

        // Passo 3: adiciona blind index columns
        Schema::table('leads', function (Blueprint $table) {
            $table->string('email_bidx', 64)->nullable()->after('email')
                ->comment('HMAC-SHA256 do email plaintext p/ busca. LGPD C4.');
            $table->string('phone_bidx', 64)->nullable()->after('phone')
                ->comment('HMAC-SHA256 do phone plaintext p/ busca. LGPD C4.');

            $table->index(['tenant_id', 'email_bidx'], 'leads_tenant_email_bidx_idx');
            $table->index(['tenant_id', 'phone_bidx'], 'leads_tenant_phone_bidx_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
            $table->string('phone_bidx', 64)->nullable()->after('phone')
                ->comment('HMAC-SHA256 do phone plaintext p/ busca. LGPD C4.');

            $table->index('phone_bidx', 'users_phone_bidx_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_tenant_email_bidx_idx');
            $table->dropIndex('leads_tenant_phone_bidx_idx');
            $table->dropColumn(['email_bidx', 'phone_bidx']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('email', 200)->nullable()->change();
            $table->string('phone', 25)->nullable()->change();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['tenant_id', 'email'], 'leads_tenant_id_email_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_phone_bidx_idx');
            $table->dropColumn('phone_bidx');
            $table->string('phone', 25)->nullable()->change();
        });
    }
};
