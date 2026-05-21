<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->enum('gender', ['masculino', 'feminino', 'nao_binario', 'outro', 'prefiro_nao_informar'])
                  ->nullable()->after('birth_date');

            $table->enum('race_color', ['branca', 'preta', 'parda', 'amarela', 'indigena', 'prefiro_nao_informar'])
                  ->nullable()->after('gender');

            $table->enum('education', [
                'sem_escolaridade',
                'fundamental_incompleto',
                'fundamental_completo',
                'medio_incompleto',
                'medio_completo',
                'superior_incompleto',
                'superior_completo',
                'pos_graduacao',
            ])->nullable()->after('race_color');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['gender', 'race_color', 'education']);
        });
    }
};
