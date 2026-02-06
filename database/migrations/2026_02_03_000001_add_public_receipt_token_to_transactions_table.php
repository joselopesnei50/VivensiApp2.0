<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('public_receipt_token', 64)->nullable()->unique()->after('id');
        });

        // Backfill existing rows with a non-enumerable token.
        // For MySQL, UUID() is available and sufficiently random for public links.
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("UPDATE `transactions` SET `public_receipt_token` = UUID() WHERE `public_receipt_token` IS NULL");
            }
        } catch (\Throwable $e) {
            // Ignore backfill errors; tokens will be generated on new receipts.
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['public_receipt_token']);
            $table->dropColumn('public_receipt_token');
        });
    }
};

