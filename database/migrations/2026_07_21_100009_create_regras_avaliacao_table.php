<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisito_legal_id')->unique();
            $table->foreign('requisito_legal_id')
                  ->references('id')->on('requisitos_legais')
                  ->onDelete('cascade');

            // Tipo A — calculável
            $table->string('modelo', 50)->nullable();          // ex: Attendance
            $table->string('campo_filtro', 50)->nullable();    // ex: gratuito
            $table->string('formula', 30)->nullable();         // percentual|existencia|contagem|soma|desvio
            $table->decimal('threshold', 8, 2)->nullable();    // ex: 20.00 (% mínimo gratuidade)
            $table->string('threshold_tipo', 10)->nullable();  // minimo|maximo|igual
            $table->string('unidade', 20)->nullable();         // ex: %|meses|registros
            // Permite que o super admin ajuste o threshold sem redeploy
            $table->boolean('threshold_editavel_admin')->default(true);
            // Permite que o tenant sobrescreva o threshold no ciclo dele (ex: deliberação do board)
            $table->boolean('threshold_editavel_tenant')->default(false);

            // Tipo B — documental
            $table->string('tipo_documento_obrigatorio', 50)->nullable();
            // estatuto|cnd_inss|crf_fgts|cnd_federal|cnd_estadual|cnd_municipal|cnas_inscricao|cmas_inscricao|cneas_registro
            $table->unsignedSmallInteger('alerta_dias_antes')->default(30);

            // Tipo C — declaratório
            $table->text('pergunta_declaracao')->nullable();
            $table->boolean('requer_anexo')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_avaliacao');
    }
};
