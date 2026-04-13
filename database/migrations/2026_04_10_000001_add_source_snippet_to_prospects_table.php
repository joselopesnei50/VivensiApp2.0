<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Origem do lead: 'maps' (Google Maps via Serper) ou 'web' (busca orgânica via Serper)
            $table->string('source', 10)->default('maps')->after('status');

            // Trecho descritivo retornado pela busca web (snippet do Google)
            // Usado como contexto adicional no prompt da Bruce AI
            $table->text('snippet')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['source', 'snippet']);
        });
    }
};
