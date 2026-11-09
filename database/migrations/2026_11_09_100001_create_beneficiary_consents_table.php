<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LGPD: termo de consentimento do beneficiário.
 *
 * - beneficiary_consents: histórico (assinatura e revogação), para provar
 *   quando, por quem e em qual versão do termo o consentimento foi dado.
 * - beneficiaries.consent_at / image_consent: situação atual, para filtrar a
 *   lista sem subconsulta. Atualizados só pelo ConsentService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->string('action', 10);              // granted | revoked
            $table->string('signer_name')->nullable();
            $table->string('signer_role', 20)->nullable(); // titular | responsavel_legal
            $table->boolean('image_use')->default(false);
            $table->date('signed_at');
            $table->string('term_version', 20)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'id']);
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->date('consent_at')->nullable()->after('status');
            $table->boolean('image_consent')->default(false)->after('consent_at');
            $table->index(['tenant_id', 'consent_at'], 'idx_beneficiaries_tenant_consent');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('idx_beneficiaries_tenant_consent');
            $table->dropColumn(['consent_at', 'image_consent']);
        });
        Schema::dropIfExists('beneficiary_consents');
    }
};
