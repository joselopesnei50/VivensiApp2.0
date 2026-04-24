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
        Schema::table('banners', function (Blueprint $table) {
            if (!Schema::hasColumn('banners', 'fabric_json')) {
                $table->longText('fabric_json')->nullable()->after('format');
            }
            if (!Schema::hasColumn('banners', 'png_path')) {
                $table->string('png_path')->nullable()->after('fabric_json');
            }
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['fabric_json', 'png_path']);
        });
    }
};
