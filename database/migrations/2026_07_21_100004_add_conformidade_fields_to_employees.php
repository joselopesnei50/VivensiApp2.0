<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('categoria_profissional', 50)->nullable()->after('role');
            // assistente_social|psicologo|pedagogo|advogado|contador|terapeuta_ocupacional
            // |fonoaudiologo|medico|enfermeiro|educador_social|monitor|outros
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('categoria_profissional');
        });
    }
};
