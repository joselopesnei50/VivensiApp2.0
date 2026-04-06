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
        // Tabela de Mensagens com auditoria e isolamento
        Schema::create('internal_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id'); // Isolamento Multi-tenant
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id')->nullable(); // Null para chat em grupo/setor
            $table->string('department')->nullable(); // Chat por setor (ex: técnico, suporte)
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Para "ocultar", mas manter para auditoria

            $table->foreign('sender_id')->references('id')->on('users');
            $table->foreign('receiver_id')->references('id')->on('users');
        });

        // Tabela de Conversas (Meta-informação)
        Schema::create('internal_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('type'); // direct, department
            $table->string('name')->nullable(); // Nome do setor ou conversa
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
        Schema::dropIfExists('internal_messages');
        Schema::dropIfExists('internal_chats');
    }
};
