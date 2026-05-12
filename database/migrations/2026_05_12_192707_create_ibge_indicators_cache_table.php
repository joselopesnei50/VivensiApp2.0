<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ibge_indicators_cache', function (Blueprint $table) {
            $table->id();
            $table->string('city_ibge_code');
            $table->string('city_name');
            $table->string('indicator_key');
            $table->text('value')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();

            $table->index(['city_ibge_code', 'indicator_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ibge_indicators_cache');
    }
};
