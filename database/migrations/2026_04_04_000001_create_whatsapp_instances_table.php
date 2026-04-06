<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Identidade na Evolution API
            $table->string('instance_name')->unique(); // Ex: vivensi_tenant_42
            $table->string('instance_token', 64)->unique(); // Token secreto para webhook URL
            $table->enum('status', ['open', 'connecting', 'close', 'paused'])->default('close');
            $table->string('phone_number', 20)->nullable(); // Número conectado (ex: 5511999999999)
            $table->string('owner_jid', 60)->nullable(); // JID retornado pela Evolution

            // Anti-Ban: Janela de horário seguro
            $table->string('safe_window_start', 5)->default('08:00'); // HH:MM
            $table->string('safe_window_end', 5)->default('21:00');   // HH:MM
            $table->string('timezone', 60)->default('America/Sao_Paulo');

            // Anti-Ban: Limites diários
            $table->unsignedInteger('daily_limit')->default(300);
            $table->unsignedInteger('messages_sent_today')->default(0);
            $table->timestamp('daily_reset_at')->nullable(); // Último reset das contagens

            // Configurações extras (JSON livre)
            $table->json('settings')->nullable();

            // Soft-delete para não perder histórico ao desconectar
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instances');
    }
};
