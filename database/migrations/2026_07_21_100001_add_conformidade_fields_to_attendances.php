<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('gratuito')->default(false)->after('description');
            $table->string('tipificacao_suas')->nullable()->after('gratuito');
            // paif|scfv|peti|paefi|abordagem_social|acolhimento_criancas_adolescentes
            // |acolhimento_adultos_familias|medida_socioeducativa|psb_outro|pse_media_outro|pse_alta_outro|outros
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['gratuito', 'tipificacao_suas']);
        });
    }
};
