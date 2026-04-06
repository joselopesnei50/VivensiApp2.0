<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ngo_grants', function (Blueprint $table) {
            // Rename columns if they exist
            if (Schema::hasColumn('ngo_grants', 'grantor_name') && !Schema::hasColumn('ngo_grants', 'agency')) {
                $table->renameColumn('grantor_name', 'agency');
            }
            if (Schema::hasColumn('ngo_grants', 'total_amount') && !Schema::hasColumn('ngo_grants', 'value')) {
                $table->renameColumn('total_amount', 'value');
            }
            if (Schema::hasColumn('ngo_grants', 'end_date') && !Schema::hasColumn('ngo_grants', 'deadline')) {
                $table->renameColumn('end_date', 'deadline');
            }
            
            // Add if missing (safeguard)
            if (!Schema::hasColumn('ngo_grants', 'deadline')) {
                $table->date('deadline')->nullable();
            }
            if (!Schema::hasColumn('ngo_grants', 'value')) {
                $table->decimal('value', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('ngo_grants', 'agency')) {
                $table->string('agency')->nullable();
            }
        });
    }

    public function down()
    {
        // Reverse is tricky without knowing exact original state, skipping for now as this is a fix-forward.
    }
};
