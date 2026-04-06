<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->select(['id', 'created_at'])
            ->where('type', 'income')
            ->whereNotNull('public_receipt_token')
            ->whereNull('public_receipt_expires_at')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();
                    DB::table('transactions')
                        ->where('id', $row->id)
                        ->update(['public_receipt_expires_at' => $createdAt->copy()->addDays(30)]);
                }
            });
    }

    public function down(): void
    {
        // No-op: do not unset expirations on rollback.
    }
};

