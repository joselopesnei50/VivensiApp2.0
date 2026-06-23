<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 1 (item 3/5).
 *
 * Presença por registro. Cada entrada é um beneficiário servido — pode ser
 * (a) cadastrado: FK beneficiary_id; (b) avulso: nome + CPF cifrado/bidx.
 *
 * CPF segue o mesmo padrão do Beneficiary:
 *  - cpf armazenado como TEXT cifrado (Crypt::encryptString no model)
 *  - cpf_bidx é HMAC-SHA256(plaintext, app.key) para dedup/lookup
 *
 * R5 LGPD: presença é PII; logs jamais devem refletir CPF. Observer pode
 * sanitizar antes de gravar AuditLog (na Fase 4/5 quando integrar).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('registros_refeicao_presencas')) {
            return;
        }

        Schema::create('registros_refeicao_presencas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('registros_refeicao')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->string('nome', 180)->nullable();
            $table->text('cpf')->nullable();       // cifrado
            $table->string('cpf_bidx', 64)->nullable(); // HMAC pra dedup
            $table->timestamps();

            $table->index('registro_id');
            $table->index('cpf_bidx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_refeicao_presencas');
    }
};
