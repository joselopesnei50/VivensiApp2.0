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
        // 1. Match EXATO do telefone normalizado (caminho seguro e preferencial).
        $exact = User::where('status', 'active')
            ->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$phone])
            ->get();

        if ($exact->count() === 1) {
            return $exact->first();
        }
        if ($exact->count() > 1) {
            // Telefones idênticos em mais de um usuário: não arriscar autenticar o tenant errado.
            Log::warning('WhatsApp Bot: telefone exato ambíguo entre múltiplos usuários', [
                'phone_suffix' => substr($phone, -4),
            ]);
            return null;
        }

        // 2. Fallback por sufixo (últimos 8 dígitos) — SÓ se houver UM único candidato.
        //    Antes o LIKE retornava o primeiro match, o que podia confundir usuários de
        //    tenants diferentes com final de número igual. Agora, se for ambíguo, recusamos.
        $candidates = User::where('status', 'active')
            ->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%' . substr($phone, -8)])
            ->get();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }
        if ($candidates->count() > 1) {
            Log::warning('WhatsApp Bot: sufixo de telefone ambíguo entre múltiplos usuários', [
                'phone_suffix' => substr($phone, -4),
            ]);
        }

        return null;
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

        // Prioridade 1: senderPn (participant phone number) — Evolution v2
        // popula esse campo quando o remoteJid vem como @lid (Linked Identity,
        // alias de privacidade do Meta 2024+). Sem essa prioridade, tentamos
        // resolver o @lid direto e falha com 'exists:false' + user_not_found.
        if (!empty($key['senderPn'])) {
            return $key['senderPn'];
        }

        // Prioridade 2: participant (em grupos, quem realmente enviou)
        if (!empty($key['participant']) && !str_contains($key['participant'], '@lid')) {
            return $key['participant'];
        }

        // Prioridade 3: remoteJid — só usa se NAO for @lid (nao ajuda encontrar user)
        $remoteJid = $key['remoteJid'] ?? null;
        if ($remoteJid && !str_contains($remoteJid, '@lid')) {
            return $remoteJid;
        }

        // Fallback: retorna o @lid mesmo (pra logging), mas findUserByPhone
        // provavelmente vai falhar
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
