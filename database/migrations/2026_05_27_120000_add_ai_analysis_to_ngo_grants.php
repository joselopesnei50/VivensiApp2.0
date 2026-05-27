<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->longText('ai_analysis')->nullable()->after('ai_proposal');
        });
    }

    public function down(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->dropColumn('ai_analysis');
        });
    }
};
