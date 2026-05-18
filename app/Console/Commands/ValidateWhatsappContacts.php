<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;

class ValidateWhatsappContacts extends Command
{
    protected $signature   = 'whatsapp:validate-contacts {--tenant= : Validar apenas um tenant} {--limit=100}';
    protected $description = 'Valida números em lote via Evolution API — resolve o problema do 9º dígito brasileiro.';

    public function handle(): void
    {
        $query = WhatsappChat::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('whatsapp_validated_at')
                  ->orWhere('whatsapp_validated_at', '<', now()->subDays(30));
            });

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        $chats = $query->limit((int) $this->option('limit'))->get();
        $this->info("Validando {$chats->count()} contatos...");

        $chats->groupBy('tenant_id')->each(function ($group, $tenantId) {
            $instance = WhatsappInstance::where('tenant_id', $tenantId)
                ->where('status', 'open')->first();

            if (!$instance) {
                $this->warn("Tenant {$tenantId}: sem instância ativa. Skipping.");
                return;
            }

            $evo     = new EvolutionApiService($instance);
            $numbers = $group->pluck('wa_id')
                ->map(fn($n) => EvolutionApiService::normalizeBrazilianPhone($n))
                ->filter()->unique()->values()->all();

            $jidMap   = $evo->checkWhatsappNumbers($numbers);
            $validated = 0;

            foreach ($group as $chat) {
                $normalized = EvolutionApiService::normalizeBrazilianPhone($chat->wa_id);
                if ($normalized && isset($jidMap[$normalized])) {
                    $chat->update([
                        'wa_jid'                => $jidMap[$normalized],
                        'whatsapp_validated_at' => now(),
                    ]);
                    $validated++;
                }
            }

            $this->info("Tenant {$tenantId}: {$validated}/{$group->count()} validados.");
        });
    }
}
