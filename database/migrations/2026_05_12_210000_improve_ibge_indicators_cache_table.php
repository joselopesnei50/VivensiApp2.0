<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ibge_indicators_cache', function (Blueprint $table) {
            // Add unique constraint to prevent duplicates
            $table->unique(['city_ibge_code', 'indicator_key'], 'ibge_cache_unique');
        });

        // Convert year column from string to integer
        DB::statement("ALTER TABLE ibge_indicators_cache MODIFY year INT NULL");
    }

    public function down()
    {
        Schema::table('ibge_indicators_cache', function (Blueprint $table) {
            $table->dropUnique('ibge_cache_unique');
        });

        DB::statement("ALTER TABLE ibge_indicators_cache MODIFY year VARCHAR(255) NULL");
    }
};
