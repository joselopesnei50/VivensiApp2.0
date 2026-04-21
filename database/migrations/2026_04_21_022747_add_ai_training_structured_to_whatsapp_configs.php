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
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->json('ai_training_structured')->nullable()->after('ai_training');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn('ai_training_structured');
        });
    }
};
