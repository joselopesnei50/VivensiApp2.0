<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'ngo_donor_id')) {
                $table->unsignedBigInteger('ngo_donor_id')->nullable()->after('project_id');
                $table->index(['tenant_id', 'ngo_donor_id']);

                // Only add FK if table exists (some dev DBs may not have NGO module migrated yet).
                if (Schema::hasTable('ngo_donors')) {
                    $table->foreign('ngo_donor_id')->references('id')->on('ngo_donors')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'ngo_donor_id')) {
                // Drop FK only if it exists (safe for dev DB variations)
                try {
                    $table->dropForeign(['ngo_donor_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropIndex(['tenant_id', 'ngo_donor_id']);
                $table->dropColumn('ngo_donor_id');
            }
        });
    }
};

