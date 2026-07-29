<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expande post_metrics com métricas específicas de cada rede:
 *   - clicks:      Facebook (post_clicks)
 *   - saved:       Instagram (saved) — indicador forte de retenção
 *   - engagement:  soma normalizada pra ranking simples (likes+comments+shares+saved)
 *   - unique_source: string identificando de onde veio ('facebook', 'instagram', 'both')
 *
 * Todos nullable/default 0 pra não quebrar dados existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_metrics', function (Blueprint $table) {
            $table->unsignedInteger('clicks')->default(0)->after('impressions');
            $table->unsignedInteger('saved')->default(0)->after('clicks');
            $table->unsignedInteger('engagement')->default(0)->after('saved');
            $table->string('source', 20)->default('facebook')->after('engagement');

            // Um post pode ter métricas separadas por rede (FB + IG), então trocamos
            // o "hasOne" implícito por um índice composto que permite N por post.
            $table->index(['scheduled_post_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('post_metrics', function (Blueprint $table) {
            $table->dropIndex(['scheduled_post_id', 'source']);
            $table->dropColumn(['clicks', 'saved', 'engagement', 'source']);
        });
    }
};
