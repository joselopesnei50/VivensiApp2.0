<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CRM de leads do tenant (Fase 5 — item 5.2 do roadmap).
     *
     * Entidade distinta de Prospect (B2B / Google Maps) e SalesLead
     * (funil comercial do super admin). Esta tabela é o "apoiador /
     * eleitor / mobilizado" capturado nos canais do tenant.
     *
     * Decisões registradas em memória:
     * - cidade como texto livre no MVP (IBGE depois)
     * - tags como JSON (normalizar depois se filtro pesado)
     * - sem campo dedicado pra opinião política — vai por tags genéricas;
     *   cifragem at-rest e categorização específica vira sub-etapa quando
     *   alinhado.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('whatsapp_chat_id')->nullable()
                ->constrained('whatsapp_chats')->nullOnDelete();
            $table->string('name', 200);
            $table->string('phone', 25)->nullable();
            $table->string('phone_normalized', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('city', 120)->nullable();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable(); // campos extras vindos de form (idade, etc)
            // Status: pending → confirmed (após double opt-in) | unsubscribed | blocked
            $table->string('status', 20)->default('pending');

            // Audit trail do opt-in original (origem do consentimento).
            $table->string('consent_origin', 50)->nullable();   // whatsapp_form | public_form | manual | import
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();
            $table->timestamp('double_opt_in_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'city']);
            $table->index(['tenant_id', 'phone_normalized']);
            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
