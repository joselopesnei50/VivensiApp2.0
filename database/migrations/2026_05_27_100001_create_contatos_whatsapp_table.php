<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('telefone', 20);
            $table->string('nome')->nullable();
            $table->boolean('opt_in')->default(false);
            $table->timestamp('opt_in_at')->nullable();
            $table->string('opt_in_origem')->nullable(); // 'bot', 'formulario', 'evento'
            $table->boolean('opt_out')->default(false);
            $table->timestamp('opt_out_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'telefone']);
            $table->index(['tenant_id', 'opt_in', 'opt_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos_whatsapp');
    }
};
