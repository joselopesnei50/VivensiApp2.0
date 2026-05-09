<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredReceiptTokens extends Command
{
    protected $signature   = 'receipts:cleanup-expired-tokens';
    protected $description = 'Revoga tokens de recibos públicos expirados (public_receipt_expires_at < now)';

    public function handle(): int
    {
        $count = Transaction::withoutGlobalScopes()
            ->whereNotNull('public_receipt_token')
            ->whereNotNull('public_receipt_expires_at')
            ->where('public_receipt_expires_at', '<', now())
            ->update([
                'public_receipt_token'      => null,
                'receipt_auth_code'         => null,
                'public_receipt_expires_at' => null,
            ]);

        Log::info("receipts:cleanup-expired-tokens — {$count} token(s) expirado(s) revogado(s).");
        $this->info("{$count} token(s) de recibo expirado(s) revogado(s).");

        return self::SUCCESS;
    }
}
