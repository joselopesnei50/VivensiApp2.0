<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formato do post social — feed (padrão) ou story (24h no Instagram).
 * Facebook Stories via API não são estáveis (Meta praticamente fechou),
 * então quando format=story só publica no Instagram.
 *
 * Reels no futuro entra aqui como 'reel' (usa mesmo enum expandido).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->string('format', 20)->default('feed')->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
