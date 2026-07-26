<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona ao módulo social-ai:
     * - reference_image_path: imagem que o user subiu (opcional)
     * - use_reference_as_final: se true, a imagem gerada pelo FLUX.1 é pulada
     *   e a referência vira a imagem final do post
     * - format: proporção alvo ('square' 1:1 padrão, 'story' 9:16)
     * - visual_style: estilo visual a temperar o prompt do FLUX.1
     *   (photorealistic | illustration | cartoon | corporate | minimalist | watercolor)
     * - caption_variations: até 3 variações de legenda geradas pelo DeepSeek
     *   pra o user escolher (o body_text guarda a "escolhida")
     */
    public function up(): void
    {
        Schema::table('ai_social_posts', function (Blueprint $table) {
            $table->string('reference_image_path')->nullable()->after('image_url');
            $table->boolean('use_reference_as_final')->default(false)->after('reference_image_path');
            $table->string('format', 20)->default('square')->after('use_reference_as_final');
            $table->string('visual_style', 30)->nullable()->after('format');
            $table->json('caption_variations')->nullable()->after('body_text');
        });
    }

    public function down(): void
    {
        Schema::table('ai_social_posts', function (Blueprint $table) {
            $table->dropColumn([
                'reference_image_path',
                'use_reference_as_final',
                'format',
                'visual_style',
                'caption_variations',
            ]);
        });
    }
};
