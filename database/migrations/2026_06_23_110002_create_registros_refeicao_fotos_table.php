<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 1 (item 2/5).
 *
 * Fotos por registro de refeição. N por registro. Cada foto preserva o lat/lng
 * e o timestamp da captura (do EXIF ou do cliente) — anti-fraude. URL aponta
 * pro S3 com path privado (acesso via signed URL).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('registros_refeicao_fotos')) {
            return;
        }

        Schema::create('registros_refeicao_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('registros_refeicao')->cascadeOnDelete();
            $table->string('s3_path', 500);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index('registro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_refeicao_fotos');
    }
};
