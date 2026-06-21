<?php

namespace App\Jobs;

use App\Models\LeadDoubleOptInToken;
use App\Models\Tenant;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * P0.2 — Dispara a mensagem de confirmação (double opt-in) via Evolution API.
 *
 * Escolhe a instância ativa do tenant (status='open'); se não houver, falha
 * silenciosamente — o token continua ativo e a tentativa pode ser reenviada.
 */
class SendDoubleOptInWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $tokenId)
    {
    }

    public function handle(): void
    {
        $token = LeadDoubleOptInToken::find($this->tokenId);
        if ($token === null || !$token->isActive()) {
            return;
        }

        $lead = $token->lead;
        if ($lead === null || empty($lead->phone_normalized)) {
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->forTenant($token->tenant_id)
            ->active()
            ->first();

        if ($instance === null) {
            Log::info('SendDoubleOptInWhatsapp: sem instância ativa para tenant', [
                'tenant_id' => $token->tenant_id,
                'lead_id'   => $lead->id,
            ]);
            return;
        }

        $tenant = Tenant::find($token->tenant_id);

        $message = $this->renderMessage(
            (string) config('whatsapp.double_opt_in.message_template'),
            $lead->name,
            $tenant?->name
        );

        $evolution = new EvolutionApiService($instance);
        $result = $evolution->sendMessage(
            $lead->phone_normalized,
            $message,
            'double_opt_in:' . $token->token
        );

        if (isset($result['error'])) {
            Log::warning('SendDoubleOptInWhatsapp: Evolution rejeitou envio', [
                'tenant_id' => $token->tenant_id,
                'lead_id'   => $lead->id,
                'error'     => $result['error'],
            ]);
            $this->release($this->backoff);
            return;
        }

        $token->update(['sent_at' => now()]);
    }

    private function renderMessage(string $template, ?string $name, ?string $tenantName): string
    {
        $nameSafe   = trim((string) $name) !== '' ? $name : 'tudo bem';
        $tenantSafe = trim((string) $tenantName) !== '' ? $tenantName : 'nossa equipe';

        return strtr($template, [
            ':nome'   => $nameSafe,
            ':tenant' => $tenantSafe,
        ]);
    }
}
