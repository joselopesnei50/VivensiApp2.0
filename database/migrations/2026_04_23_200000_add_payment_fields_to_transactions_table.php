<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'gateway_id')) {
                $table->string('gateway_id')->nullable()->after('external_id');
            }
            if (!Schema::hasColumn('transactions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('gateway_id');
            }
            if (!Schema::hasColumn('transactions', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('transactions', 'gateway_id') ? 'gateway_id' : null,
                Schema::hasColumn('transactions', 'paid_at')    ? 'paid_at'    : null,
                Schema::hasColumn('transactions', 'plan_id')    ? 'plan_id'    : null,
            ]));
        });
    }
};
