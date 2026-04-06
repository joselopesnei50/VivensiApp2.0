<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            if (!Schema::hasColumn('ngo_grants', 'contract_number')) {
                $table->string('contract_number', 100)->nullable()->after('agency');
            }
            if (!Schema::hasColumn('ngo_grants', 'start_date')) {
                $table->date('start_date')->nullable()->after('value');
            }
            if (!Schema::hasColumn('ngo_grants', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            if (Schema::hasColumn('ngo_grants', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('ngo_grants', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('ngo_grants', 'contract_number')) {
                $table->dropColumn('contract_number');
            }
        });
    }
};

