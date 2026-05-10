<?php

namespace App\Console\Commands;

use App\Models\RaffleTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupRaffleReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raffles:cleanup-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera bilhetes de rifa cujas reservas expiraram (mais de 30 minutos)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // UPDATE atômico em vez de get()+foreach — elimina race condition quando
        // dois workers executam simultaneamente
        $count = RaffleTicket::where('status', 'pending')
            ->where('reserved_at', '<', Carbon::now()->subMinutes(30))
            ->update([
                'status'      => 'available',
                'buyer_name'  => null,
                'buyer_email' => null,
                'buyer_phone' => null,
                'reserved_at' => null,
            ]);

        if ($count > 0) {
            Log::info("CleanupRaffleReservations: {$count} bilhetes liberados.");
        }

        $this->info("Sucesso: {$count} bilhetes de rifa foram liberados.");

        return self::SUCCESS;
    }
}
