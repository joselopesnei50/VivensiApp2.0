<?php

namespace App\Console\Commands;

use App\Mail\RaffleReservationsExpiring;
use App\Mail\RaffleTicketReleased;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reservas de rifa: o PIX vai direto para a ONG, que confirma à mão.
 * - faltando REMINDER_HOURS_BEFORE horas para vencer, avisa a ONG (uma vez);
 * - passadas RESERVATION_HOURS horas sem confirmação, libera os números e avisa o comprador.
 */
class CleanupRaffleReservations extends Command
{
    protected $signature = 'raffles:cleanup-reservations';

    protected $description = 'Avisa a ONG sobre reservas de rifa perto de vencer e libera as vencidas (sem pagamento confirmado)';

    public function handle(): int
    {
        $reminded = $this->sendReminders();
        $released = $this->releaseExpired();

        if ($released > 0 || $reminded > 0) {
            Log::info("CleanupRaffleReservations: {$released} bilhetes liberados, {$reminded} avisos de vencimento.");
        }
        $this->info("Sucesso: {$released} bilhetes de rifa foram liberados; {$reminded} avisos enviados.");

        return self::SUCCESS;
    }

    private function releaseExpired(): int
    {
        $limit = Carbon::now()->subHours(RaffleTicket::RESERVATION_HOURS);

        // Trava as linhas e libera só o que ainda está pendente — seguro com dois workers ao mesmo tempo.
        $released = DB::transaction(function () use ($limit) {
            $tickets = RaffleTicket::where('status', 'pending')
                ->where('reserved_at', '<', $limit)
                ->lockForUpdate()
                ->get();

            if ($tickets->isEmpty()) {
                return collect();
            }

            RaffleTicket::whereIn('id', $tickets->pluck('id'))
                ->where('status', 'pending')
                ->update(RaffleTicket::RELEASED_ATTRIBUTES);

            return $tickets; // cópia com os dados do comprador, para o e-mail
        });

        $this->notifyBuyers($released);

        return $released->count();
    }

    private function notifyBuyers(Collection $tickets): void
    {
        $tickets->filter(fn ($t) => $t->buyer_email)
            ->groupBy(fn ($t) => $t->raffle_id . '|' . ($t->reservation_token ?: strtolower($t->buyer_email)))
            ->each(function (Collection $group) {
                $raffle = Raffle::withoutGlobalScope('tenant')->with('tenant')->find($group->first()->raffle_id);
                if (!$raffle) {
                    return;
                }
                try {
                    Mail::to($group->first()->buyer_email)->send(new RaffleTicketReleased($raffle, $group));
                } catch (\Throwable $e) {
                    Log::error('Rifa: falha ao avisar comprador da reserva vencida: ' . $e->getMessage());
                }
            });
    }

    private function sendReminders(): int
    {
        $hoursBefore = RaffleTicket::RESERVATION_HOURS - RaffleTicket::REMINDER_HOURS_BEFORE;
        $tickets = RaffleTicket::where('status', 'pending')
            ->whereNull('reminder_sent_at')
            ->where('reserved_at', '<', Carbon::now()->subHours($hoursBefore))
            ->where('reserved_at', '>=', Carbon::now()->subHours(RaffleTicket::RESERVATION_HOURS))
            ->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $raffles = Raffle::withoutGlobalScope('tenant')->with('tenant')
            ->whereIn('id', $tickets->pluck('raffle_id')->unique())->get()->keyBy('id');

        $sent = 0;
        $tickets->groupBy(fn ($t) => optional($raffles->get($t->raffle_id))->tenant_id)
            ->each(function (Collection $byTenant, $tenantId) use ($raffles, &$sent) {
                $tenant = optional($raffles->get($byTenant->first()->raffle_id))->tenant;
                if (!$tenant) {
                    return;
                }

                $orders = $byTenant
                    ->groupBy(fn ($t) => $t->raffle_id . '|' . ($t->reservation_token ?: strtolower((string) $t->buyer_email)))
                    ->map(function (Collection $g) use ($raffles) {
                        $raffle = $raffles->get($g->first()->raffle_id);
                        return [
                            'raffle'  => $raffle->title,
                            'buyer'   => (string) $g->first()->buyer_name,
                            'phone'   => $g->first()->buyer_phone,
                            'numbers' => $g->sortBy('number')->map(fn ($t) => '#' . str_pad($t->number, 3, '0', STR_PAD_LEFT))->implode(', '),
                            'total'   => (float) $raffle->ticket_price * $g->count(),
                            'expires' => $g->min('reserved_at')->copy()->addHours(RaffleTicket::RESERVATION_HOURS)->format('d/m H:i'),
                        ];
                    })->values()->all();

                $owner = $tenant->users()->orderBy('id')->first();
                $to = $tenant->report_email ?? $owner?->email;

                // Marca antes de enviar: se o e-mail falhar, não repete a cada 15 minutos.
                RaffleTicket::whereIn('id', $byTenant->pluck('id'))->update(['reminder_sent_at' => now()]);

                if ($to) {
                    try {
                        Mail::to($to)->send(new RaffleReservationsExpiring($tenant, $orders));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::error('Rifa: falha ao avisar a ONG sobre reservas vencendo: ' . $e->getMessage());
                    }
                }
            });

        return $sent;
    }
}
