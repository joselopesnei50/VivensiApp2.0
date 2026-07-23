<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radar_findings', function (Blueprint $table) {
            $table->boolean('auto_approved')->default(false)->after('is_relevant');
        });
    }

    public function down(): void
    {
        Schema::table('radar_findings', function (Blueprint $table) {
            $table->dropColumn('auto_approved');
        });
    }
};
