<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadTimelineItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * LeadService — Fase 5 (item 5.2 do roadmap).
 *
 * Camada única de acesso ao CRM de leads do tenant. Centraliza:
 * - Normalização de telefone BR (chave de unicidade prática).
 * - Idempotência ao criar lead a partir de canais (form WhatsApp,
 *   form público, import).
 * - Registro auditável de consentimento (LGPD Art. 7º/8º — origem,
 *   timestamp, IP, UA).
 * - Itens de timeline (notas manuais, eventos sistêmicos, sugestões
 *   do Bruce).
 *
 * Decisões registradas em memória:
 * - phone como chave principal de unicidade dentro do tenant.
 * - SEM campo dedicado pra opinião política — entra via tags genéricas.
 *   Cifragem at-rest vira sub-etapa quando alinhado.
 */
class LeadService
{
    public const PHONE_MIN_LEN = 8;  // fixo de 4 dígitos local mínimo (raro mas válido em algumas localidades)
    public const PHONE_MAX_LEN = 15; // E.164 max

    /**
     * Normaliza um telefone BR para a forma canônica de comparação:
     * só dígitos, com prefixo 55 quando o número parece brasileiro.
     *
     * Casos cobertos:
     * - '(11) 99999-8888'       → '5511999998888'
     * - '+55 11 99999-8888'     → '5511999998888'
     * - '5511999998888'         → '5511999998888'
     * - '11999998888'           → '5511999998888'
     * - '99998888'              → '99998888' (sem DDD, mantém como está)
     *
     * Devolve null se a string não tiver dígitos suficientes.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $len    = strlen($digits);

        if ($len < self::PHONE_MIN_LEN) {
            return null;
        }
        if ($len > self::PHONE_MAX_LEN) {
            // Truncar pode mascarar erro; melhor descartar.
            return null;
        }

        // BR com DDD sem prefixo internacional → adiciona 55
        if (($len === 10 || $len === 11) && substr($digits, 0, 2) !== '55') {
            return '55' . $digits;
        }
        return $digits;
    }

    /**
     * Idempotente: se já existe lead com mesmo (tenant, phone_normalized),
     * devolve esse. Senão cria novo. Atributos extras mesclam (sem sobrescrever
     * dados existentes — só preenche campos vazios).
     *
     * @param array<string,mixed> $attrs
     */
    public function findOrCreateByPhone(Tenant $tenant, string $phone, array $attrs = []): Lead
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === null) {
            throw new InvalidArgumentException('Telefone inválido para criar lead.');
        }

        return DB::transaction(function () use ($tenant, $phone, $normalized, $attrs) {
            $existing = Lead::where('tenant_id', $tenant->id)
                ->where('phone_normalized', $normalized)
                ->first();

            if ($existing) {
                $this->fillIfEmpty($existing, $attrs);
                return $existing;
            }

            return Lead::create(array_merge([
                'tenant_id'        => $tenant->id,
                'phone'            => $phone,
                'phone_normalized' => $normalized,
                'name'             => $attrs['name'] ?? '(sem nome)',
                'status'           => Lead::STATUS_PENDING,
            ], $attrs, [
                // Garante que esses três não são sobrescritos via $attrs:
                'tenant_id'        => $tenant->id,
                'phone'            => $phone,
                'phone_normalized' => $normalized,
            ]));
        });
    }

    /**
     * Variação por e-mail. Mesma idempotência usando (tenant, email).
     *
     * @param array<string,mixed> $attrs
     */
    public function findOrCreateByEmail(Tenant $tenant, string $email, array $attrs = []): Lead
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido para criar lead.');
        }

        return DB::transaction(function () use ($tenant, $email, $attrs) {
            $existing = Lead::where('tenant_id', $tenant->id)
                ->where('email', $email)
                ->first();

            if ($existing) {
                $this->fillIfEmpty($existing, $attrs);
                return $existing;
            }

            return Lead::create(array_merge([
                'tenant_id' => $tenant->id,
                'email'     => $email,
                'name'      => $attrs['name'] ?? '(sem nome)',
                'status'    => Lead::STATUS_PENDING,
            ], $attrs, [
                'tenant_id' => $tenant->id,
                'email'     => $email,
            ]));
        });
    }

    /**
     * Registra evento de consentimento + atualiza campos derivados no Lead
     * (consent_at/origin/ip pro opt_in original; double_opt_in_at pro
     * double opt-in; unsubscribed_at + status pro opt_out).
     */
    public function recordConsent(
        Lead $lead,
        string $type,
        string $origin,
        ?Request $request = null,
        ?array $payload = null
    ): LeadConsent {
        $ip = $request?->ip();
        $ua = $request !== null ? mb_substr((string) $request->userAgent(), 0, 1000) : null;

        $consent = LeadConsent::create([
            'tenant_id'   => $lead->tenant_id,
            'lead_id'     => $lead->id,
            'type'        => $type,
            'origin'      => $origin,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'payload'     => $payload,
            'recorded_at' => now(),
        ]);

        // Reflete no estado do Lead (audit primário fica no consents).
        switch ($type) {
            case LeadConsent::TYPE_OPT_IN:
                if ($lead->consent_at === null) {
                    $lead->update([
                        'consent_at'         => now(),
                        'consent_origin'     => $origin,
                        'consent_ip'         => $ip,
                        'consent_user_agent' => $ua,
                    ]);
                }
                break;

            case LeadConsent::TYPE_DOUBLE_OPT_IN:
                $lead->update([
                    'double_opt_in_at' => now(),
                    'status'           => Lead::STATUS_CONFIRMED,
                ]);
                break;

            case LeadConsent::TYPE_OPT_OUT:
                $lead->update([
                    'unsubscribed_at' => now(),
                    'status'          => Lead::STATUS_UNSUBSCRIBED,
                ]);
                break;
        }

        $this->addTimelineItem($lead, LeadTimelineItem::TYPE_CONSENT,
            sprintf('Consentimento registrado: %s (%s)', $type, $origin), null, $payload);

        return $consent;
    }

    public function addTimelineItem(
        Lead $lead,
        string $type,
        string $body,
        ?User $author = null,
        ?array $meta = null
    ): LeadTimelineItem {
        $item = LeadTimelineItem::create([
            'tenant_id' => $lead->tenant_id,
            'lead_id'   => $lead->id,
            'author_id' => $author?->id,
            'type'      => $type,
            'body'      => $body,
            'meta'      => $meta,
        ]);

        $lead->update(['last_interaction_at' => now()]);
        return $item;
    }

    /**
     * Atalho semântico — equivalente a recordConsent($lead, OPT_OUT, ...).
     */
    public function unsubscribe(Lead $lead, string $origin, ?string $reason = null, ?Request $request = null): LeadConsent
    {
        return $this->recordConsent($lead, LeadConsent::TYPE_OPT_OUT, $origin, $request,
            $reason !== null ? ['reason' => $reason] : null);
    }

    /**
     * Preenche apenas atributos vazios do lead — não sobrescreve dados
     * já capturados anteriormente. Útil em find-or-create iterativo.
     *
     * @param array<string,mixed> $attrs
     */
    private function fillIfEmpty(Lead $lead, array $attrs): void
    {
        $updates = [];
        foreach (['name', 'email', 'city', 'whatsapp_chat_id'] as $field) {
            if (empty($lead->{$field}) && !empty($attrs[$field])) {
                $updates[$field] = $attrs[$field];
            }
        }
        // tags: merge em vez de sobrescrever
        if (!empty($attrs['tags']) && is_array($attrs['tags'])) {
            $merged = array_values(array_unique(array_merge((array) ($lead->tags ?? []), $attrs['tags'])));
            $updates['tags'] = $merged;
        }
        if (!empty($attrs['meta']) && is_array($attrs['meta'])) {
            $updates['meta'] = array_merge((array) ($lead->meta ?? []), $attrs['meta']);
        }
        if ($updates !== []) {
            $lead->update($updates);
        }
    }
}
