<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 2 — criada agora para o schema ser completo desde a Fase 1
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radar_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index('rm_tenant_idx');
            $table->unsignedBigInteger('radar_finding_id');
            $table->unsignedTinyInteger('score')->default(0)->index('rm_score_idx'); // 0–100
            $table->json('score_reasons')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'radar_finding_id'], 'rm_tenant_finding_uniq');
            $table->foreign('radar_finding_id')->references('id')->on('radar_findings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radar_matches');
    }
};
