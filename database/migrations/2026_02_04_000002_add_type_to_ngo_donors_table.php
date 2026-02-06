<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngo_donors', function (Blueprint $table) {
            if (!Schema::hasColumn('ngo_donors', 'type')) {
                $table->string('type', 32)->default('individual')->after('phone');
                $table->index(['tenant_id', 'type']);
            }
        });

        // Backfill existing rows (if any).
        try {
            DB::table('ngo_donors')->whereNull('type')->update(['type' => 'individual']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::table('ngo_donors', function (Blueprint $table) {
            if (Schema::hasColumn('ngo_donors', 'type')) {
                $table->dropIndex(['tenant_id', 'type']);
                $table->dropColumn('type');
            }
        });
    }
};

