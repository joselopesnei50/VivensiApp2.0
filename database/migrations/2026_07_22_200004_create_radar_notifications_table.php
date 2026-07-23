<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 2 — criada agora para o schema ser completo desde a Fase 1
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radar_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index('rn_tenant_idx');
            $table->unsignedBigInteger('radar_finding_id');
            $table->string('channel', 20);   // painel | email | whatsapp
            $table->timestamp('sent_at');
            $table->string('feedback', 20)->nullable();    // util | nao_util
            $table->timestamp('feedback_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'radar_finding_id', 'channel'], 'rn_tenant_finding_channel_uniq');
            $table->foreign('radar_finding_id')->references('id')->on('radar_findings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radar_notifications');
    }
};
