<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Projeção segura de WhatsappInstance para respostas JSON.
 *
 * Fix 2 do relatório de segurança (2026-07-05): antes o controller devolvia
 * o model bruto (`$instance->fresh()`), o que expunha:
 *  - `settings.proxy_url` — pode conter credenciais
 *  - `settings.restricted_reason` — sinaliza método de detecção interno
 *  - `settings.instance_data` cru vindo da Evolution API
 *  - `daily_reset_at`, `updated_at`, `deleted_at` — meta desnecessário
 *
 * Este Resource expõe apenas campos operacionais úteis pro painel e
 * ATRIBUTOS DERIVADOS de settings (warming, taxa de resposta, restrição)
 * — nunca o dicionário settings inteiro.
 *
 * `instance_token` e `instance_token_bidx` já saem do model via $hidden,
 * mas ficam aqui documentados por completude.
 */
class WhatsappInstanceResource extends JsonResource
{
    public function toArray($request): array
    {
        $settings = (array) ($this->settings ?? []);

        return [
            'id'                  => $this->id,
            'instance_name'       => $this->instance_name,
            'phone_number'        => $this->phone_number,
            'status'              => $this->status,
            'safe_window_start'   => $this->safe_window_start,
            'safe_window_end'     => $this->safe_window_end,
            'timezone'            => $this->timezone,
            'daily_limit'         => (int) $this->daily_limit,
            'messages_sent_today' => (int) $this->messages_sent_today,
            'created_at'          => $this->created_at?->toIso8601String(),

            // Campos derivados de settings — expostos SÓ os relevantes pro
            // painel. Sem `proxy_url`, `restricted_reason`, `instance_data`,
            // nem qualquer chave crua não listada aqui.
            'warming' => [
                'active'        => (bool) ($settings['warming_mode'] ?? false),
                'profile'       => $settings['warming_profile'] ?? 'default',
                'started_at'    => $settings['warming_started_at'] ?? null,
            ],
            'response_rate' => [
                'rate_7d'    => isset($settings['response_rate_7d'])
                    ? (float) $settings['response_rate_7d']
                    : null,
                'updated_at' => $settings['response_rate_updated_at'] ?? null,
            ],
            'restriction' => [
                'active' => !empty($settings['restricted_until'])
                    && strtotime($settings['restricted_until']) > time(),
                'until'  => $settings['restricted_until'] ?? null,
            ],
            // Sinal booleano — o gestor precisa saber que existe proxy sem
            // ver o valor (que pode ter credenciais).
            'has_proxy' => !empty($settings['proxy_url']),
        ];
    }
}
