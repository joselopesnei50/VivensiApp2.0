<?php

namespace App\Services\Messaging;

use App\Jobs\SendDoubleOptInWhatsapp;
use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadDoubleOptInToken;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * P0.2 — Orquestra o fluxo de double opt-in via WhatsApp:
 *  - requestFor(): gera token e enfileira o disparo
 *  - processInbound(): casa resposta do contato contra confirm/opt-out keywords
 */
class DoubleOptInService
{
    public function __construct(private LeadService $leads)
    {
    }

    /**
     * Cria um token e dispara o Job de envio. Idempotente: se já existe
     * token ativo para o lead, retorna ele sem reenfileirar.
     */
    public function requestFor(Lead $lead): ?LeadDoubleOptInToken
    {
        if (empty($lead->phone_normalized)) {
            return null;
        }
        if ($lead->isConfirmed()) {
            return null;
        }

        $active = LeadDoubleOptInToken::where('lead_id', $lead->id)
            ->active()
            ->first();
        if ($active !== null) {
            return $active;
        }

        $ttl = (int) config('whatsapp.double_opt_in.ttl_hours', 72);

        $token = LeadDoubleOptInToken::create([
            'tenant_id'  => $lead->tenant_id,
            'lead_id'    => $lead->id,
            'token'      => Str::random(48),
            'expires_at' => now()->addHours(max(1, $ttl)),
        ]);

        SendDoubleOptInWhatsapp::dispatch($token->id);

        return $token;
    }

    /**
     * Processa uma mensagem inbound de um lead pendente. Devolve true se a
     * mensagem fechou o opt-in (confirm ou opt-out); false se foi ignorada.
     */
    public function processInbound(Lead $lead, string $messageBody, ?Request $request = null): bool
    {
        $token = LeadDoubleOptInToken::where('lead_id', $lead->id)
            ->active()
            ->orderByDesc('id')
            ->first();
        if ($token === null) {
            return false;
        }

        $normalized = $this->normalize($messageBody);
        if ($normalized === '') {
            return false;
        }

        $confirm = (array) config('whatsapp.double_opt_in.confirm_keywords', []);
        $optOut  = (array) config('whatsapp.double_opt_in.opt_out_keywords', []);

        if ($this->matches($normalized, $confirm)) {
            $this->leads->recordConsent(
                $lead,
                LeadConsent::TYPE_DOUBLE_OPT_IN,
                'whatsapp_double_opt_in',
                $request,
                ['token_id' => $token->id]
            );
            $token->update(['confirmed_at' => now()]);
            return true;
        }

        if ($this->matches($normalized, $optOut)) {
            $this->leads->recordConsent(
                $lead,
                LeadConsent::TYPE_OPT_OUT,
                'whatsapp_double_opt_in',
                $request,
                ['token_id' => $token->id, 'reason' => 'reply_no']
            );
            $token->update(['opted_out_at' => now()]);
            return true;
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        // Remove acentos. Str::ascii (portable-ascii) é determinístico em
        // qualquer SO/locale — iconv //TRANSLIT vira 'n~ao' no Windows e
        // pode virar 'n?o' no Linux com locale C, quebrando o opt-out.
        $text = Str::ascii($text);
        // Mantém só letras, dígitos e espaço; colapsa whitespace.
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        return $text;
    }

    /**
     * Match exato em palavra-chave (token único) OU substring delimitada
     * por borda (evita "para" disparar "pa", "não obrigado" disparar "sim").
     *
     * @param list<string> $keywords
     */
    private function matches(string $normalized, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            $kw = trim(mb_strtolower((string) $kw));
            if ($kw === '') {
                continue;
            }
            if ($normalized === $kw) {
                return true;
            }
            if (preg_match('/(^|\s)' . preg_quote($kw, '/') . '($|\s)/', $normalized) === 1) {
                return true;
            }
        }
        return false;
    }
}
