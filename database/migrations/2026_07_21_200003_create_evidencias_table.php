<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('avaliacao_requisito_id');
            $table->foreign('avaliacao_requisito_id')->references('id')->on('avaliacoes_requisito')->onDelete('cascade');

            $table->string('evidenciavel_type')->nullable(); // App\Models\Attachment etc.
            $table->unsignedBigInteger('evidenciavel_id')->nullable();
            $table->string('tipo', 20);                     // calculado/documento/declaracao
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->index(['evidenciavel_type', 'evidenciavel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
