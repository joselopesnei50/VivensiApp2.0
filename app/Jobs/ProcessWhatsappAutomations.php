<?php

namespace App\Jobs;

use App\Models\NgoDonor;
use App\Models\SponsorshipDeal;
use App\Models\Tenant;
use App\Models\WhatsappAutomation;
use App\Models\WhatsappAutomationLog;
use App\Models\WhatsappChat;
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

            $tenant   = Tenant::find($automation->tenant_id);
            $orgName  = $tenant?->name ?? 'nossa organização';
            $contacts = $this->resolveContacts($automation);
            $evo      = new EvolutionApiService($instance);

            // Resolve JIDs corretos via WhatsApp (corrige 9º dígito brasileiro)
            $normalizedPhones = [];
            foreach ($contacts as $c) {
                $n = EvolutionApiService::normalizeBrazilianPhone($c['phone'] ?? '');
                if ($n) $normalizedPhones[] = $n;
            }

            $jidMap             = [];
            $fallbackNormalized = false;
            if (!empty($normalizedPhones)) {
                try {
                    $jidMap = $evo->checkWhatsappNumbers($normalizedPhones);
                } catch (\Throwable $e) {
                    Log::warning("WhatsappAutomation [{$automation->id}]: checkWhatsappNumbers falhou, usando fallback — {$e->getMessage()}");
                    $fallbackNormalized = true;
                }
            }

            foreach ($contacts as $contact) {
                $normalized = EvolutionApiService::normalizeBrazilianPhone($contact['phone'] ?? '');
                if (!$normalized) continue;

                $sendTo = $jidMap[$normalized] ?? ($fallbackNormalized ? $normalized : null);
                if ($sendTo === null) {
                    Log::info("WhatsappAutomation [{$automation->id}]: {$normalized} não encontrado no WhatsApp — pulando.");
                    continue;
                }

                // Anti-spam usa número normalizado (chave estável no banco)
                $alreadySent = $automation->send_once
                    ? WhatsappAutomationLog::where('automation_id', $automation->id)
                        ->where('contact_phone', $normalized)
                        ->where('status', 'sent')
                        ->exists()
                    : WhatsappAutomationLog::where('automation_id', $automation->id)
                        ->where('contact_phone', $normalized)
                        ->where('sent_at', '>=', now()->subHours(24))
                        ->exists();

                if ($alreadySent) continue;

                $message = $automation->renderMessage(
                    $contact['name'] ?? 'Olá',
                    $orgName,
                    $contact['days'] ?? 0
                );

                try {
                    $evo->sendMessage($sendTo, $message, null, rand(2, 5));

                    WhatsappAutomationLog::create([
                        'automation_id' => $automation->id,
                        'tenant_id'     => $automation->tenant_id,
                        'contact_phone' => $normalized,
                        'contact_name'  => $contact['name'] ?? null,
                        'message_sent'  => $message,
                        'status'        => 'sent',
                        'sent_at'       => now(),
                    ]);

                    Log::info("WhatsappAutomation [{$automation->id}]: mensagem enviada para {$sendTo} (normalizado: {$normalized}).");

                    // Delay entre envios para comportamento orgânico
                    sleep(rand(3, 8));

                } catch (\Throwable $e) {
                    WhatsappAutomationLog::create([
                        'automation_id' => $automation->id,
                        'tenant_id'     => $automation->tenant_id,
                        'contact_phone' => $normalized,
                        'contact_name'  => $contact['name'] ?? null,
                        'message_sent'  => $message,
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                        'sent_at'       => now(),
                    ]);

                    Log::error("WhatsappAutomation [{$automation->id}]: falha ao enviar para {$sendTo} — {$e->getMessage()}");
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

            // Contatos WhatsApp sem mensagem há X dias
            'no_contact_days' => WhatsappChat::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')
                ->whereNull('blocked_at')
                ->where(function ($q) use ($cutoff) {
                    $q->where('last_inbound_at', '<=', $cutoff)
                      ->orWhereNull('last_inbound_at');
                })
                ->get()
                ->map(fn($c) => [
                    'name'  => $c->contact_name ?? 'Olá',
                    'phone' => $c->contact_phone ?: $c->wa_id,
                    'days'  => $c->last_inbound_at ? (int) now()->diffInDays($c->last_inbound_at) : $days,
                ])
                ->toArray(),

            // Conversas abertas sem resposta há X dias
            'open_conversation_days' => WhatsappChat::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')
                ->whereNull('blocked_at')
                ->where(function ($q) use ($cutoff) {
                    $q->where('last_inbound_at', '<=', $cutoff)
                      ->orWhereNull('last_inbound_at');
                })
                ->get()
                ->map(fn($c) => [
                    'name'  => $c->contact_name ?? 'Olá',
                    'phone' => $c->contact_phone ?: $c->wa_id,
                    'days'  => $c->last_inbound_at ? (int) now()->diffInDays($c->last_inbound_at) : $days,
                ])
                ->toArray(),

            // Doadores sem doação há X dias
            'donor_inactive_days' => NgoDonor::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('phone')
                ->where(function ($q) use ($cutoff) {
                    $q->where('last_donation_at', '<=', $cutoff)
                      ->orWhereNull('last_donation_at');
                })
                ->get()
                ->map(fn($d) => [
                    'name'  => $d->name,
                    'phone' => $d->phone,
                    'days'  => $d->last_donation_at ? (int) now()->diffInDays($d->last_donation_at) : $days,
                ])
                ->toArray(),

            // Patrocínios parados na fase de proposta há X dias
            'sponsorship_stale_days' => SponsorshipDeal::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('stage', 'proposta')
                ->whereNotNull('contact_phone')
                ->where('updated_at', '<=', $cutoff)
                ->get()
                ->map(fn($s) => [
                    'name'  => $s->contact_name,
                    'phone' => $s->contact_phone,
                    'days'  => (int) now()->diffInDays($s->updated_at),
                ])
                ->toArray(),

            // Contatos N dias após confirmar opt-in
            'after_opt_in_days' => WhatsappChat::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')
                ->whereNull('blocked_at')
                ->whereDate('opt_in_at', '<=', $cutoff)
                ->get()
                ->map(fn($c) => [
                    'name'  => $c->contact_name ?? 'Olá',
                    'phone' => $c->contact_phone ?: $c->wa_id,
                    'days'  => (int) now()->diffInDays($c->opt_in_at),
                ])
                ->toArray(),

            // keyword_received é processado em tempo real pelo webhook — não no batch diário
            default => [],
        };
    }
}
