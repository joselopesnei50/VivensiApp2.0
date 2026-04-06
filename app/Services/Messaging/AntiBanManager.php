<?php

namespace App\Services\Messaging;

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Log;

/**
 * AntiBanManager — Gerencia o compliance e cadência de envio.
 * 
 * Responsabilidades:
 * - Verificar janela de horário seguro
 * - Verificar e contabilizar limite diário real
 * - Simular comportamento humano (presença "digitando")
 * - Gerar delays randômicos para envio orgânico
 */
class AntiBanManager
{
    protected EvolutionApiService $api;

    public function __construct(EvolutionApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Verifica se a instância pode enviar uma mensagem agora.
     * Usa a lógica encapsulada no Model WhatsappInstance.
     */
    public function canSendMessage(WhatsappInstance $instance): bool
    {
        if (!$instance->isWithinSafeWindow()) {
            Log::info("AntiBan: [{$instance->instance_name}] Fora da janela de horário ({$instance->safe_window_start}–{$instance->safe_window_end}).");
            return false;
        }

        if ($instance->hasReachedDailyLimit()) {
            Log::info("AntiBan: [{$instance->instance_name}] Limite diário atingido ({$instance->messages_sent_today}/{$instance->daily_limit}).");
            return false;
        }

        return true;
    }

    /**
     * Simula digitação humana: envia presença "composing" + sleep randômico.
     * Chamar ANTES de enviar a mensagem.
     */
    public function simulateHumanTyping(WhatsappInstance $instance, string $remoteJid): void
    {
        try {
            $this->api->sendPresence($instance->instance_name, $remoteJid, 'composing');
            sleep(rand(2, 5));
        } catch (\Throwable $e) {
            Log::warning("AntiBan: Falha ao simular digitação para {$remoteJid}: " . $e->getMessage());
        }
    }

    /**
     * Retorna delay randômico em milissegundos para o payload da Evolution API.
     * Simula o tempo de digitação de forma orgânica.
     */
    public function getRandomDelayMs(): int
    {
        return rand(1500, 4000);
    }

    /**
     * Registra o envio: incrementa contador diário da instância.
     * Chamar APÓS o envio bem-sucedido.
     */
    public function recordSent(WhatsappInstance $instance): void
    {
        $instance->incrementDailyCount();
    }
}
