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
        // Respostas Rápidas (Canned Responses)
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('title'); // Atalho (ex: /saudacao)
            $table->text('content');
            $table->timestamps();
        });

        // Notas Internas (CRM Notes)
        Schema::create('whatsapp_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('whatsapp_chats')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable(); // Quem criou a nota (pode ser null se for sistema/IA)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->text('content');
            $table->string('type')->default('manual'); // manual, ai_insight, system_log
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_notes');
        Schema::dropIfExists('canned_responses');
    }
};
