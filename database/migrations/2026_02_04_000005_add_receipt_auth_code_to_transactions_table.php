<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'receipt_auth_code')) {
                $table->string('receipt_auth_code', 16)->nullable()->unique()->after('public_receipt_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'receipt_auth_code')) {
                $table->dropUnique(['receipt_auth_code']);
                $table->dropColumn('receipt_auth_code');
            }
        });
    }
};

