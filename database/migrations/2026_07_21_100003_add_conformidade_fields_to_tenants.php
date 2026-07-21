<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('cnae_principal', 10)->nullable()->after('document');
            $table->date('data_fundacao')->nullable()->after('cnae_principal');
            $table->string('area_atuacao_cebas')->nullable()->after('data_fundacao');
            // assistencia_social|saude|educacao|multipla
            $table->string('cnas_numero', 30)->nullable()->after('area_atuacao_cebas');
            $table->date('cnas_validade')->nullable()->after('cnas_numero');
            $table->string('cmas_numero', 30)->nullable()->after('cnas_validade');
            $table->date('cmas_validade')->nullable()->after('cmas_numero');
            $table->string('cneas_codigo', 20)->nullable()->after('cmas_validade');
            $table->decimal('receita_bruta_anual_ref', 15, 2)->nullable()->after('cneas_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'cnae_principal', 'data_fundacao', 'area_atuacao_cebas',
                'cnas_numero', 'cnas_validade', 'cmas_numero', 'cmas_validade',
                'cneas_codigo', 'receita_bruta_anual_ref',
            ]);
        });
    }
};
