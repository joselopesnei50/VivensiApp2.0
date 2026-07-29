<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suporte a carrossel Instagram (múltiplas mídias por post).
 *
 * Estrutura do JSON media_items:
 *   [
 *     {"url": "https://vivensi.app.br/storage/...", "type": "image"},
 *     {"url": "https://vivensi.app.br/storage/...", "type": "image"},
 *     ...
 *   ]
 *
 * Regras de compat:
 * - media_url + media_type continuam existindo (posts antigos e single-media).
 * - Quando media_items existe e tem >= 1 item, o publisher usa carrossel.
 * - Sempre que gravar media_items, o controller também popula media_url com
 *   a primeira URL (fallback pra Facebook, que hoje só suporta 1 foto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->json('media_items')->nullable()->after('media_url');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->dropColumn('media_items');
        });
    }
};
