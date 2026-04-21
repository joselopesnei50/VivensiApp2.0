<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->longText('ai_proposal')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->dropColumn('ai_proposal');
        });
    }
};
