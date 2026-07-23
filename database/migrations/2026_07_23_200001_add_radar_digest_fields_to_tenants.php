<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('radar_digest_channel', 20)->nullable()->after('radar_areas');
            $table->tinyInteger('radar_min_score')->default(30)->after('radar_digest_channel');
            $table->timestamp('radar_last_digest_at')->nullable()->after('radar_min_score');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['radar_digest_channel', 'radar_min_score', 'radar_last_digest_at']);
        });
    }
};
