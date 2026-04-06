<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('transactions', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('transactions', 'approval_status')) {
                $table->string('approval_status')->nullable()->default('approved')->after('receipt_path');
            }
            if (!Schema::hasColumn('transactions', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('transactions', 'external_id')) {
                $table->string('external_id')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('transactions', 'origem_verba_id')) {
                $table->unsignedBigInteger('origem_verba_id')->nullable()->after('external_id');
            }
            if (!Schema::hasColumn('transactions', 'volunteer_id')) {
                $table->unsignedBigInteger('volunteer_id')->nullable()->after('origem_verba_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $cols = ['attachment_path', 'receipt_path', 'approval_status', 'category_id', 'external_id', 'origem_verba_id', 'volunteer_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
