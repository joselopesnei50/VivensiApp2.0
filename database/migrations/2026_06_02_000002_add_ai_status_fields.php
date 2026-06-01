<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->string('ai_proposal_status', 20)->nullable()->after('ai_proposal');
            $table->string('ai_analysis_status', 20)->nullable()->after('ai_analysis');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('ai_summary_status', 20)->nullable()->after('ai_summary_at');
        });
    }

    public function down(): void
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            $table->dropColumn(['ai_proposal_status', 'ai_analysis_status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('ai_summary_status');
        });
    }
};
