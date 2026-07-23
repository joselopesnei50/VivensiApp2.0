<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radar_findings', function (Blueprint $table) {
            $table->id();

            // ── Fase 1 ────────────────────────────────────────────────────────
            $table->string('source', 30);                          // querido_diario | transferegov
            $table->string('territory_ibge', 7)->nullable()->index('rf_territory_ibge_idx');
            $table->string('dedupe_hash', 64)->unique();
            $table->string('title');
            $table->text('excerpt');
            $table->string('source_url');
            $table->date('published_at')->index('rf_published_at_idx');
            $table->string('keyword_matched');
            $table->json('raw_payload');
            $table->string('status', 20)->default('novo')->index('rf_status_idx'); // novo | aprovado | rejeitado
            $table->unsignedBigInteger('curated_by')->nullable();
            $table->timestamp('curated_at')->nullable();

            // ── Fase 4 (IA) — criadas agora como nullable ─────────────────────
            $table->json('areas')->nullable();
            $table->text('object_summary')->nullable();
            $table->date('deadline')->nullable();
            $table->decimal('value_total', 15, 2)->nullable();
            $table->timestamp('ai_processed_at')->nullable();

            $table->timestamps();

            $table->foreign('curated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radar_findings');
    }
};
