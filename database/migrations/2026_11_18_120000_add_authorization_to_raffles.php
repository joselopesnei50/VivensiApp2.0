<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Número do Certificado de Autorização da Secretaria de Prêmios e Apostas (SPA/MF)
 * que a organização obtém no SCPC antes de vender números (Lei 13.019/2014, art. 84-B).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->string('authorization_number', 60)->nullable()->after('rules');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->dropColumn('authorization_number');
        });
    }
};
