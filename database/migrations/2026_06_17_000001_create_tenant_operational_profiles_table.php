<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria o perfil operacional por tenant — base da Fase 1 do roadmap.
     * Relação 1:1 com tenants. Tenants sem registro caem no comportamento
     * default ("outro"), por isso a tabela começa vazia (sem backfill).
     */
    public function up(): void
    {
        Schema::create('tenant_operational_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->string('categoria', 40)->default('outro');
            $table->text('instrucao')->nullable();
            $table->json('vocabulario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_operational_profiles');
    }
};
