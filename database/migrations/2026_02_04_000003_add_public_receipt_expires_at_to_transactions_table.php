<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'public_receipt_expires_at')) {
                $table->timestamp('public_receipt_expires_at')->nullable()->after('public_receipt_token');
                $table->index(['public_receipt_expires_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'public_receipt_expires_at')) {
                $table->dropIndex(['public_receipt_expires_at']);
                $table->dropColumn('public_receipt_expires_at');
            }
        });
    }
};

