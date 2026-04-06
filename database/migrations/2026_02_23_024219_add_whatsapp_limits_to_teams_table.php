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
        Schema::table('tenants', function (Blueprint $table) {
            $table->integer('whatsapp_daily_limit')->default(20)->after('type'); // Limite diário inicial baixo
            $table->integer('whatsapp_warmup_days')->default(0)->after('whatsapp_daily_limit'); // Dias de aquecimento
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_daily_limit', 'whatsapp_warmup_days']);
        });
    }
};
