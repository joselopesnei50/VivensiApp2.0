<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            // Rename columns to match new code requirements
            if (Schema::hasColumn('ngo_grants', 'grantor_name')) {
                $table->renameColumn('grantor_name', 'agency');
            }
            if (Schema::hasColumn('ngo_grants', 'total_amount')) {
                $table->renameColumn('total_amount', 'value');
            }
            if (Schema::hasColumn('ngo_grants', 'end_date')) {
                $table->renameColumn('end_date', 'deadline');
            }
        });
    }

    public function down()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
             if (Schema::hasColumn('ngo_grants', 'agency')) {
                $table->renameColumn('agency', 'grantor_name');
            }
            if (Schema::hasColumn('ngo_grants', 'value')) {
                $table->renameColumn('value', 'total_amount');
            }
            if (Schema::hasColumn('ngo_grants', 'deadline')) {
                $table->renameColumn('deadline', 'end_date');
            }
        });
    }
};
