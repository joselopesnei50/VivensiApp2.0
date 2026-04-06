<?php

namespace App\Console\Commands;

use App\Models\RaffleTicket;
use Illuminate\Console\Command;
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
    public function handle()
    {
        $expiredTickets = RaffleTicket::where('status', 'pending')
            ->where('reserved_at', '<', Carbon::now()->subMinutes(30))
            ->get();

        $count = $expiredTickets->count();

        foreach ($expiredTickets as $ticket) {
            $ticket->update([
                'status' => 'available',
                'buyer_name' => null,
                'buyer_email' => null,
                'buyer_phone' => null,
                'reserved_at' => null,
            ]);
        }

        $this->info("Sucesso: {$count} bilhetes de rifa foram liberados.");
        
        return 0;
    }
}
