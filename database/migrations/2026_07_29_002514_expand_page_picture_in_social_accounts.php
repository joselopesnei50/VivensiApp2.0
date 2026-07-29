<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bug encontrado ao conectar Facebook Page em produção (2026-07-29):
 * SQLSTATE[22001] Data too long for column 'page_picture' — URL da foto
 * que o Facebook retorna passa dos 255 chars por causa dos parâmetros
 * de assinatura CDN. Aumenta pra TEXT (~64KB) — tem folga sobrando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->text('page_picture')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('page_picture')->nullable()->change();
        });
    }
};
