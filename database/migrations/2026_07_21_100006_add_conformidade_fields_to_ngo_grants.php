<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->string('modalidade', 30)->nullable()->after('status');
            // fomento|colaboracao|termo_parceria|convenio|outro
            $table->string('numero_instrumento', 50)->nullable()->after('modalidade');
            $table->string('orgao_concedente_codigo', 20)->nullable()->after('numero_instrumento');
        });
    }

    public function down(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->dropColumn(['modalidade', 'numero_instrumento', 'orgao_concedente_codigo']);
        });
    }
};
