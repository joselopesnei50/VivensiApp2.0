<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            $table->decimal('executed_value', 15, 2)->nullable()->after('planned_value');
        });
    }

    public function down(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            $table->dropColumn('executed_value');
        });
    }
};
