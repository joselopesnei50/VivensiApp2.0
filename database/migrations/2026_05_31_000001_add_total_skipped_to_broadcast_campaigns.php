<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->unsignedInteger('total_skipped')->default(0)->after('total_failed');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn('total_skipped');
        });
    }
};
