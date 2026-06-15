<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etiquetas customizáveis por tenant para o módulo WhatsApp.
     *
     * Substitui a lista hardcoded em chat.blade.php (7 etiquetas fixas:
     * Novo Lead, Suporte, Venda, Urgente, VIP, Agendado, Concluído) por
     * uma lista por tenant — cada ONG nomeia as etiquetas que precisa.
     *
     * Operação compatível: tabela nova, ninguém consulta ainda. UI antiga
     * continua funcionando até o próximo deploy (Etapa B) trocar a fonte.
     */
    public function up(): void
    {
        Schema::create('whatsapp_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->string('slug', 40);
            $table->string('color', 7);              // #RRGGBB do badge
            $table->string('background', 7);          // #RRGGBB do fundo do badge
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Slug é único por tenant — duas etiquetas do mesmo tenant
            // não podem ter o mesmo slug. Mas tenants diferentes podem ter
            // 'venda' cada um.
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_labels');
    }
};
