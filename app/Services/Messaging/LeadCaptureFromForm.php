<?php

namespace App\Services\Messaging;

use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadTimelineItem;
use App\Models\Tenant;
use App\Models\WhatsappFormAnswer;
use App\Models\WhatsappFormSession;
use App\Services\LeadService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * LeadCaptureFromForm — Fase 5 (sub-etapa 5.A.2).
 *
 * Conecta o WhatsappFormEngine ao CRM: quando uma WhatsappFormSession vira
 * COMPLETED, mapeia answers → Lead + OPT_IN + timeline. Idempotente (chamar
 * duas vezes pra mesma sessão devolve o mesmo lead sem duplicar consent
 * principal, pois LeadService.recordConsent só atualiza consent_at original).
 *
 * Mapeamento de field_keys reservados (lowercase):
 * - phone  → chave principal de unicidade (com fallback pro contact_phone
 *            ou wa_id do chat quando o form não pergunta).
 * - email  → chave alternativa quando não há phone.
 * - name   → coluna `name` do lead.
 * - city   → coluna `city` do lead.
 * - tags   → vira array (split por vírgula).
 * - resto  → entra em `meta` chaveado pelo field_key.
 *
 * Falhas (telefone inválido sem email e sem fallback de chat) NÃO bloqueiam
 * a conclusão do form — só logam e o caller segue com `null`.
 */
class LeadCaptureFromForm
{
    private LeadService $leads;

    public function __construct(LeadService $leads)
    {
        $this->leads = $leads;
    }

    /**
     * Captura/atualiza Lead a partir de uma sessão concluída.
     * Devolve o Lead criado/encontrado ou null se nada pôde ser identificado.
     *
     * Idempotente via session.lead_id: se a sessão já foi capturada antes
     * (lead_id != null), devolve o lead existente sem duplicar OPT_IN nem
     * timeline (auditoria LGPD não pode ter consent fantasma).
     */
    public function capture(WhatsappFormSession $session): ?Lead
    {
        if ($session->status !== WhatsappFormSession::STATUS_COMPLETED) {
            return null;
        }

        if ($session->lead_id !== null) {
            return Lead::find($session->lead_id);
        }

        $tenant = Tenant::find($session->tenant_id);
        if ($tenant === null) {
            return null;
        }

        $answers = WhatsappFormAnswer::where('session_id', $session->id)->get();
        $extract = $this->extract($answers);

        $chat       = $session->chat()->first();
        $phoneInput = $extract['phone'] ?? ($chat?->contact_phone ?: $chat?->wa_id);
        $email      = $extract['email'] ?? null;

        $baseAttrs = array_filter([
            'name'             => $extract['name'] ?? null,
            'city'             => $extract['city'] ?? null,
            'whatsapp_chat_id' => $chat?->id,
            'tags'             => $extract['tags'] ?? null,
            'meta'             => $extract['meta'] ?? null,
        ], fn ($v) => $v !== null && $v !== []);

        try {
            $lead = null;
            if (!empty($phoneInput)) {
                $lead = $this->leads->findOrCreateByPhone($tenant, (string) $phoneInput, $baseAttrs);
            } elseif (!empty($email)) {
                $lead = $this->leads->findOrCreateByEmail($tenant, $email, $baseAttrs);
            }
        } catch (InvalidArgumentException $e) {
            // Telefone/email inválido — não bloquear UX no WA. Tenta cair pro outro canal.
            Log::warning('LeadCaptureFromForm: identificador inválido', [
                'session_id' => $session->id,
                'reason'     => $e->getMessage(),
            ]);
            if (!empty($email)) {
                try {
                    $lead = $this->leads->findOrCreateByEmail($tenant, $email, $baseAttrs);
                } catch (Throwable $e2) {
                    Log::warning('LeadCaptureFromForm: fallback por email também falhou', [
                        'session_id' => $session->id,
                        'reason'     => $e2->getMessage(),
                    ]);
                    return null;
                }
            } else {
                return null;
            }
        }

        if ($lead === null) {
            Log::info('LeadCaptureFromForm: nenhum identificador (phone/email/chat) — pulando.', [
                'session_id' => $session->id,
            ]);
            return null;
        }

        $form = $session->form()->first();
        $formId   = $form?->id;
        $formName = $form?->name ?? '(form removido)';

        // OPT_IN explícito — completar form no WA é manifestação ativa.
        // LeadService.recordConsent só preenche consent_at na primeira chamada.
        $this->leads->recordConsent(
            $lead,
            LeadConsent::TYPE_OPT_IN,
            "whatsapp_form:{$formId}",
            null, // sem Request — canal não-HTTP
            [
                'form_id'    => $formId,
                'form_name'  => $formName,
                'session_id' => $session->id,
                'chat_id'    => $chat?->id,
            ]
        );

        $this->leads->addTimelineItem(
            $lead,
            LeadTimelineItem::TYPE_FORM_COMPLETED,
            sprintf('Formulário "%s" concluído via WhatsApp.', $formName),
            null,
            [
                'form_id'    => $formId,
                'session_id' => $session->id,
                'answers'    => $this->summarizeAnswers($answers),
            ]
        );

        // Persiste o link na sessão (campo já existe no schema).
        if ($session->lead_id !== $lead->id) {
            $session->update(['lead_id' => $lead->id]);
        }

        return $lead;
    }

    /**
     * Separa answers em campos reservados (phone/email/name/city/tags) e meta.
     *
     * @param iterable<WhatsappFormAnswer> $answers
     * @return array{phone?:string,email?:string,name?:string,city?:string,tags?:array<int,string>,meta?:array<string,string>}
     */
    private function extract(iterable $answers): array
    {
        $reserved = ['phone', 'email', 'name', 'city', 'tags'];
        $out      = [];
        $meta     = [];

        foreach ($answers as $a) {
            $key  = mb_strtolower(trim((string) $a->field_key));
            $text = trim((string) $a->answer_text);
            if ($key === '' || $text === '') {
                continue;
            }
            if (in_array($key, $reserved, true)) {
                if ($key === 'tags') {
                    $out['tags'] = $this->splitTags($text);
                } else {
                    $out[$key] = $text;
                }
            } else {
                $meta[$key] = $text;
            }
        }

        if ($meta !== []) {
            $out['meta'] = $meta;
        }
        return $out;
    }

    /**
     * @return array<int,string>
     */
    private function splitTags(string $raw): array
    {
        $parts = preg_split('/\s*,\s*/', $raw) ?: [];
        $tags  = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $tags[] = $p;
            }
        }
        return array_values(array_unique($tags));
    }

    /**
     * @param iterable<WhatsappFormAnswer> $answers
     * @return array<string,string>
     */
    private function summarizeAnswers(iterable $answers): array
    {
        $out = [];
        foreach ($answers as $a) {
            $key = (string) $a->field_key;
            if ($key === '') {
                continue;
            }
            $out[$key] = mb_substr((string) $a->answer_text, 0, 500);
        }
        return $out;
    }
}
