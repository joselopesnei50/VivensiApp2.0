<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o "Guia do Bruce" ao módulo /marketing:
     * - execution_guide (JSON): checklist prescritivo com passos e
     *   ferramentas Vivensi (broadcast, landing, social-ai, prospecting)
     * - guide_status: 'pending' | 'ready' | 'failed'
     * - guide_generated_at: quando a 2ª chamada DeepSeek foi processada
     *
     * O guide é opcional — se a segunda chamada falhar, o plano principal
     * (mindmap_data) continua utilizável.
     */
    public function up(): void
    {
        Schema::table('marketing_plans', function (Blueprint $table) {
            $table->json('execution_guide')->nullable()->after('mindmap_data');
            $table->string('guide_status', 20)->default('pending')->after('execution_guide');
            $table->timestamp('guide_generated_at')->nullable()->after('guide_status');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_plans', function (Blueprint $table) {
            $table->dropColumn(['execution_guide', 'guide_status', 'guide_generated_at']);
        });
    }
};
