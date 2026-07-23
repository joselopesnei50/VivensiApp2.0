<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('radar_ibge_code', 7)->nullable()->after('cneas_codigo');
            $table->json('radar_areas')->nullable()->after('radar_ibge_code');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['radar_ibge_code', 'radar_areas']);
        });
    }
};
