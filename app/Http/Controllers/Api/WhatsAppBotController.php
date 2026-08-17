<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use App\Services\EvolutionApiService;
use App\Jobs\ProcessWhatsAppBotMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppBotController — Bot de Gestão Interna do Vivensi
 *
 * Recebe webhooks da Evolution API e despacha para processamento assíncrono.
 */
class WhatsAppBotController extends Controller
{
    private string $botInstanceName;
    private string $botPhone;
    private bool   $botEnabled;

    public function __construct()
    {
        $this->botEnabled      = (bool) SystemSetting::getValue('bot_enabled', false);
        $this->botPhone        = SystemSetting::getValue('bot_phone', '') ?? '';
        $this->botInstanceName = SystemSetting::getValue('bot_instance_name', '') ?? '';
    }

    /**
     * Ponto de entrada do Webhook da Evolution API para o bot de gestão.
     */
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        // Debug 2026-08-17: escreve DIRETO no arquivo (bypass Log/stack/level)
        // pra confirmar se o request chega a executar este metodo. Se este file
        // NAO aparecer apos webhook, o request morre antes (middleware, cache
        // de rota, outro handler). Remover apos diagnostico.
        @file_put_contents(
            storage_path('logs/bot_debug.txt'),
            sprintf(
                "[%s] HIT ip=%s ua=%s event=%s tok=%s bkeys=%s\n",
                date('c'),
                $request->ip(),
                substr((string) $request->userAgent(), 0, 40),
                $request->input('event') ?? $request->input('type') ?? '(none)',
                $request->query('bot_token') ? 'yes' : 'no',
                implode(',', array_keys($request->all()))
            ),
            FILE_APPEND
        );

        Log::warning('WhatsApp Bot: request recebido', [
            'ip'         => $request->ip(),
            'event'      => $request->input('event') ?? $request->input('type'),
            'has_token'  => $request->query('bot_token') ? 'yes' : 'no',
            'body_keys'  => array_keys($request->all()),
            'data_keys'  => is_array($request->input('data')) ? array_keys($request->input('data')) : null,
        ]);

        // Debug helper — grava a saída em bot_debug.txt junto com o Log::warning
        $trace = function (string $tag, array $ctx = []) {
            @file_put_contents(
                storage_path('logs/bot_debug.txt'),
                sprintf("  → [%s] EXIT=%s %s\n", date('H:i:s'), $tag, json_encode($ctx, JSON_UNESCAPED_UNICODE)),
                FILE_APPEND
            );
        };

        // 1. Verificação de status
        if (!$this->botEnabled) {
            $trace('disabled');
            Log::warning('WhatsApp Bot: DISABLED - retornando 200 sem processar');
            return response()->json(['status' => 'disabled'], 200);
        }

        // 2. Segurança: token via query string (?bot_token=xxx) ou header X-Bot-Secret
        // A Evolution API não envia headers customizados, por isso o token é embutido na URL.
        $secret = config('services.whatsapp.bot_secret');
        if ($secret) {
            $provided = $request->query('bot_token') ?? $request->header('X-Bot-Secret');
            if (!is_string($provided) || !hash_equals((string) $secret, $provided)) {
                $trace('unauthorized_token', ['has_provided' => (bool) $provided]);
                Log::warning('WhatsApp Bot: token inválido', ['ip' => $request->ip()]);
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        $data = $request->all();
        $event = $data['event'] ?? ($data['type'] ?? '');

        // 3. Filtragem de eventos
        if (!in_array($event, ['messages.upsert', 'MESSAGES_UPSERT', 'message'])) {
            $trace('event_not_recognized', ['event' => $event]);
            Log::warning('WhatsApp Bot: EVENT NAO RECONHECIDO', ['event' => $event]);
            return response()->json(['status' => 'ignored', 'reason' => 'event_type'], 200);
        }

        $waId = $this->extractWaId($data);
        $text = $this->extractText($data);

        $trace('after_extract', [
            'wa_id_full'   => $waId,
            'text_preview' => $text ? mb_substr($text, 0, 30) : null,
            'text_len'     => $text ? mb_strlen($text) : 0,
        ]);

        if (!$waId || !$text || str_contains($waId, '@g.us')) {
            $trace('ignored_payload', [
                'has_wa_id' => (bool) $waId,
                'has_text'  => (bool) $text,
                'is_group'  => $waId ? str_contains($waId, '@g.us') : null,
            ]);
            Log::warning('WhatsApp Bot: PAYLOAD SEM waId/text OU eh grupo', [
                'wa_id_present' => (bool) $waId,
                'text_present'  => (bool) $text,
                'is_group'      => $waId ? str_contains($waId, '@g.us') : null,
            ]);
            return response()->json(['status' => 'ignored', 'reason' => 'payload'], 200);
        }

        $cleanPhone = preg_replace('/\D/', '', explode('@', $waId)[0]);
        if ($cleanPhone === $this->botPhone) {
            $trace('self_ignored', ['phone_suffix' => substr($cleanPhone, -4)]);
            Log::warning('WhatsApp Bot: self (mensagem do proprio bot) - ignorando');
            return response()->json(['status' => 'self'], 200);
        }

        // 4. Identificação do Usuário
        $user = $this->findUserByPhone($cleanPhone);

        if (!$user) {
            $trace('user_not_found', [
                'phone_suffix' => substr($cleanPhone, -4),
                'wa_id_used'   => $waId,
                'key_dump'     => $data['data']['key'] ?? $data['key'] ?? null,
            ]);
            Log::warning('WhatsApp Bot: USER NAO ENCONTRADO', [
                'phone_suffix' => substr($cleanPhone, -4),
            ]);
            $this->sendDirect($waId, "❌ *Número não cadastrado no Vivensi.*\n\nPeça ao administrador para vincular seu WhatsApp ao sistema.");
            return response()->json(['status' => 'unauthorized'], 200);
        }

        // 5. Despacho Assíncrono (Melhoria de Performance)
        $trace('queued', ['user' => $user->name, 'user_id' => $user->id]);
        Log::warning("WhatsApp Bot: Mensagem de [{$user->name}] enviada para fila.");

        ProcessWhatsAppBotMessage::dispatch($user, $waId, $text);

        return response()->json(['status' => 'queued'], 200);
    }

    // ─── Auxiliares ───────────────────────────────────────────────────────────

    private function findUserByPhone(string $phone): ?User
    {
        // NOTA 2026-08-17: users.phone eh AES-256 encrypted e phone_bidx eh
        // HMAC do valor exato cadastrado (com formatacao). REGEXP_REPLACE no
        // ciphertext nao funciona — busca correta eh via bidx.

        // 1. Tenta bidx pra formatos comuns do numero (fastpath, indice).
        $variants = $this->phoneVariants($phone);
        $key      = (string) config('app.key');

        foreach ($variants as $variant) {
            $bidx = hash_hmac('sha256', $variant, $key);
            $u    = User::where('status', 'active')
                ->where('phone_bidx', $bidx)
                ->first();
            if ($u) {
                return $u;
            }
        }

        // 2. Fallback lento: itera users ativos, descriptografa via accessor,
        // compara digitos limpos. O(n) mas ok pra bot interno (poucos users).
        $matches = collect();
        User::where('status', 'active')
            ->whereNotNull('phone')
            ->select(['id', 'name', 'phone', 'phone_bidx', 'role', 'tenant_id', 'status'])
            ->chunk(500, function ($chunk) use ($phone, &$matches) {
                foreach ($chunk as $u) {
                    $plain = $u->phone; // accessor descriptografa
                    if (!$plain) continue;
                    $clean = preg_replace('/\D/', '', (string) $plain);
                    // Match exato OU sufixo (ultimos 8 digitos — cobre casos
                    // sem DDI 55 ou com 9 digito celular ausente)
                    if ($clean === $phone || substr($clean, -8) === substr($phone, -8)) {
                        $matches->push($u);
                    }
                }
            });

        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() > 1) {
            Log::warning('WhatsApp Bot: telefone ambiguo entre multiplos users', [
                'phone_suffix' => substr($phone, -4),
                'match_count'  => $matches->count(),
            ]);
        }

        return null;
    }

    /**
     * Gera variantes plausiveis do telefone pra tentar match direto no bidx
     * (evita fullscan). Cobre com/sem DDI 55, com/sem 9 do celular, com
     * mascara padrao BR.
     */
    private function phoneVariants(string $phone): array
    {
        $phone = preg_replace('/\D/', '', $phone);
        $variants = [$phone];

        // Remove DDI 55 se presente
        if (str_starts_with($phone, '55') && strlen($phone) >= 12) {
            $variants[] = substr($phone, 2);
        }
        // Adiciona DDI 55 se ausente
        if (!str_starts_with($phone, '55')) {
            $variants[] = '55' . $phone;
        }

        // Mascaras comuns cadastradas manualmente
        if (strlen($phone) >= 10) {
            $local = str_starts_with($phone, '55') ? substr($phone, 2) : $phone;
            if (strlen($local) === 11) {
                $ddd = substr($local, 0, 2);
                $num = substr($local, 2);
                $variants[] = "($ddd) " . substr($num, 0, 5) . '-' . substr($num, 5);
                $variants[] = "$ddd " . substr($num, 0, 5) . '-' . substr($num, 5);
            }
        }

        return array_unique($variants);
    }

    private function sendDirect(string $waId, string $text): void
    {
        try {
            $phone = preg_replace('/\D/', '', explode('@', $waId)[0]);
            if (!$this->botInstanceName) return;

            $context = new \stdClass();
            $context->evolution_instance_name  = $this->botInstanceName;
            $context->evolution_instance_token = null;

            $evo = new EvolutionApiService($context);
            $evo->sendMessage($phone, $text);
        } catch (\Throwable $e) {
            Log::error("WhatsApp Bot Controller Error: " . $e->getMessage());
        }
    }

    private function extractWaId(array $data): ?string
    {
        // Coleta a estrutura 'key' de onde ela tipicamente aparece nos
        // payloads Evolution v2.
        $key = $data['data']['key']
            ?? $data['data']['message']['key']
            ?? $data['key']
            ?? [];

        // Prioridade 1: remoteJidAlt — Evolution v2.3+ popula esse campo com
        // o numero REAL (@s.whatsapp.net) quando addressingMode=lid. LID
        // (Linked Identity, Meta 2024+) eh alias sintetico usado por usuarios
        // com privacidade avancada; sem remoteJidAlt findUserByPhone falha.
        if (!empty($key['remoteJidAlt']) && !str_contains($key['remoteJidAlt'], '@lid')) {
            return $key['remoteJidAlt'];
        }

        // Prioridade 2: senderPn (participant phone number — alguns builds)
        if (!empty($key['senderPn'])) {
            return $key['senderPn'];
        }

        // Prioridade 3: participant (em grupos, quem enviou)
        if (!empty($key['participant']) && !str_contains($key['participant'], '@lid')) {
            return $key['participant'];
        }

        // Prioridade 4: remoteJid — só usa se NAO for @lid
        $remoteJid = $key['remoteJid'] ?? null;
        if ($remoteJid && !str_contains($remoteJid, '@lid')) {
            return $remoteJid;
        }

        // Fallback: retorna @lid mesmo (pra logging), mas findUser vai falhar
        return $remoteJid;
    }

    private function extractText(array $data): ?string
    {
        return $data['data']['message']['conversation']
            ?? $data['data']['message']['extendedTextMessage']['text']
            ?? $data['message']['conversation']
            ?? $data['message']['extendedTextMessage']['text']
            ?? null;
    }
}
