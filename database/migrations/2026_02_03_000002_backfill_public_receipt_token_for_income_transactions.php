<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill tokens for existing income transactions (receipts).
        // Use PHP-generated UUIDs to avoid DB-specific functions.
        DB::table('transactions')
            ->select(['id'])
            ->where('type', 'income')
            ->whereNull('public_receipt_token')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('transactions')
                        ->where('id', $row->id)
                        ->update(['public_receipt_token' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        // No-op: do not wipe tokens on rollback.
    }
};

