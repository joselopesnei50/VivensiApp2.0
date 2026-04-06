<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Configurações do Z-API por Inquilino (Tenant)
        Schema::create('whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('instance_id')->nullable();
            $table->string('token')->nullable();
            $table->string('client_token')->nullable();
            $table->text('ai_training')->nullable(); // Campo para "treinar" a IA
            $table->boolean('is_active')->default(false);
            $table->boolean('ai_enabled')->default(true);
            $table->timestamps();
        });

        // Histórico de Conversas
        Schema::create('whatsapp_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('wa_id')->unique(); // ID do contato no WhatsApp
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('open'); // open, closed, pending
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users'); 
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // Mensagens
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('whatsapp_chats')->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->text('content');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('type')->default('chat'); // chat, image, document
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_chats');
        Schema::dropIfExists('whatsapp_configs');
    }
};
