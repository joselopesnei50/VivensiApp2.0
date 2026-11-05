<?php

use App\Support\TrafficClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas do blog: separa robôs de pessoas e guarda origem e dispositivo
 * de cada visita. Reclassifica o histórico a partir do User-Agent e do
 * Referer já gravados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->boolean('is_bot')->default(false)->after('referer');
            $table->string('source', 12)->nullable()->after('is_bot');
            $table->string('device', 8)->nullable()->after('source');
            $table->index(['is_bot', 'created_at'], 'idx_post_views_bot_date');
        });

        DB::table('post_views')->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $r) {
                DB::table('post_views')->where('id', $r->id)->update([
                    'is_bot' => TrafficClassifier::isBot($r->user_agent),
                    'source' => TrafficClassifier::source($r->referer),
                    'device' => TrafficClassifier::device($r->user_agent),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex('idx_post_views_bot_date');
            $table->dropColumn(['is_bot', 'source', 'device']);
        });
    }
};
