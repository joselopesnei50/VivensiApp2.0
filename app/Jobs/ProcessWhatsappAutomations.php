<?php

namespace App\Jobs;

use App\Models\NgoDonor;
use App\Models\SponsorshipDeal;
use App\Models\Tenant;
use App\Models\WhatsappAutomation;
use App\Models\WhatsappAutomationLog;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappAutomations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function handle(): void
    {
        $automations = WhatsappAutomation::where('is_active', true)->get();

        foreach ($automations as $automation) {
            if (!$automation->isWithinSendWindow()) {
                Log::info("WhatsappAutomation [{$automation->id}]: fora da janela de envio. Pulando.");
                continue;
            }

            $instance = WhatsappInstance::where('tenant_id', $automation->tenant_id)
                ->where('status', 'open')
                ->first();

            if (!$instance) {
                Log::warning("WhatsappAutomation [{$automation->id}]: nenhuma instância ativa para tenant {$automation->tenant_id}.");
                continue;
            }

            $tenant    = Tenant::find($automation->tenant_id);
            $orgName   = $tenant?->name ?? 'nossa organização';
            $contacts  = $this->resolveContacts($automation);

            foreach ($contacts as $contact) {
                $phone = preg_replace('/\D/', '', $contact['phone'] ?? '');
                if (!$phone || strlen($phone) < 10) continue;

                // Evitar reenvio: se já enviou esta automação para este contato nas últimas 24h
                $alreadySent = WhatsappAutomationLog::where('automation_id', $automation->id)
                    ->where('contact_phone', $phone)
                    ->where('sent_at', '>=', now()->subHours(24))
                    ->exists();

                if ($alreadySent) continue;

                $message = $automation->renderMessage($contact['name'] ?? 'Olá', $orgName);

                try {
                    $evo = new EvolutionApiService($instance);
                    $evo->sendMessage($phone, $message, null, rand(2, 5));

                    WhatsappAutomationLog::create([
                        'automation_id' => $automation->id,
                        'tenant_id'     => $automation->tenant_id,
                        'contact_phone' => $phone,
                        'contact_name'  => $contact['name'] ?? null,
                        'message_sent'  => $message,
                        'status'        => 'sent',
                        'sent_at'       => now(),
                    ]);

                    Log::info("WhatsappAutomation [{$automation->id}]: mensagem enviada para {$phone}.");

                    // Delay entre envios para comportamento orgânico
                    sleep(rand(3, 8));

                } catch (\Throwable $e) {
                    WhatsappAutomationLog::create([
                        'automation_id' => $automation->id,
                        'tenant_id'     => $automation->tenant_id,
                        'contact_phone' => $phone,
                        'contact_name'  => $contact['name'] ?? null,
                        'message_sent'  => $message,
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                        'sent_at'       => now(),
                    ]);

                    Log::error("WhatsappAutomation [{$automation->id}]: falha ao enviar para {$phone} — {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Resolve a lista de contatos com base no trigger da automação.
     * Retorna array de ['name' => '...', 'phone' => '...']
     */
    private function resolveContacts(WhatsappAutomation $automation): array
    {
        $tenantId = $automation->tenant_id;
        $days     = $automation->trigger_days;
        $cutoff   = now()->subDays($days);

        return match ($automation->trigger) {

            // Doadores sem interação há X dias
            'donor_inactive_days' => NgoDonor::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('phone')
                ->where(function ($q) use ($cutoff) {
                    $q->where('last_donation_at', '<=', $cutoff)
                      ->orWhereNull('last_donation_at');
                })
                ->get()
                ->map(fn($d) => ['name' => $d->name, 'phone' => $d->phone])
                ->toArray(),

            // Patrocínios parados na fase de proposta há X dias
            'sponsorship_stale_days' => SponsorshipDeal::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('stage', 'proposta')
                ->whereNotNull('contact_phone')
                ->where('updated_at', '<=', $cutoff)
                ->get()
                ->map(fn($s) => ['name' => $s->contact_name, 'phone' => $s->contact_phone])
                ->toArray(),

            default => [],
        };
    }
}
