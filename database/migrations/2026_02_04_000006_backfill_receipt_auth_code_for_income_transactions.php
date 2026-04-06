<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->select(['id'])
            ->where('type', 'income')
            ->whereNull('receipt_auth_code')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    // 16 hex chars (8 bytes)
                    $code = strtoupper(bin2hex(random_bytes(8)));

                    // Extremely unlikely collision, but keep it safe.
                    while (DB::table('transactions')->where('receipt_auth_code', $code)->exists()) {
                        $code = strtoupper(bin2hex(random_bytes(8)));
                    }

                    DB::table('transactions')
                        ->where('id', $row->id)
                        ->update(['receipt_auth_code' => $code]);
                }
            });
    }

    public function down(): void
    {
        // No-op
    }
};

