<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshots_conformidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->unsignedBigInteger('ciclo_conformidade_id')->nullable();
            $table->foreign('ciclo_conformidade_id')->references('id')->on('ciclos_conformidade')->onDelete('set null');

            $table->decimal('indice_geral', 5, 2)->default(0);
            $table->decimal('indice_cebas', 5, 2)->default(0);
            $table->decimal('indice_suas', 5, 2)->default(0);
            $table->decimal('indice_mrosc', 5, 2)->default(0);
            $table->unsignedSmallInteger('total_verde')->default(0);
            $table->unsignedSmallInteger('total_amarelo')->default(0);
            $table->unsignedSmallInteger('total_vermelho')->default(0);
            $table->unsignedSmallInteger('total_nao_aplicavel')->default(0);
            $table->timestamp('snapshotado_em');
            $table->timestamps();

            $table->index(['tenant_id', 'snapshotado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots_conformidade');
    }
};
