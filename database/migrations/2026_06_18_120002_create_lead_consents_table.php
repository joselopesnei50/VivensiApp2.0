<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Histórico imutável dos eventos de consentimento (LGPD).
     * Cada opt-in, double opt-in, opt-out e preference_update vira uma
     * linha aqui — fundamental pra auditoria fiscal.
     */
    public function up(): void
    {
        Schema::create('lead_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            // type: opt_in | double_opt_in | opt_out | preference_update
            $table->string('type', 30);
            $table->string('origin', 50)->nullable();    // whatsapp_form | public_form | manual | import | sms | email
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();         // versão de termo, texto exibido, contexto
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['lead_id', 'type']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_consents');
    }
};
