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
        // 1. Verificação de status
        if (!$this->botEnabled) {
            return response()->json(['status' => 'disabled'], 200);
        }

        // 2. Segurança: token via query string (?bot_token=xxx) ou header X-Bot-Secret
        // A Evolution API não envia headers customizados, por isso o token é embutido na URL.
        $secret = config('services.whatsapp.bot_secret');
        if ($secret) {
            $provided = $request->query('bot_token') ?? $request->header('X-Bot-Secret');
            if ($provided !== $secret) {
                Log::warning('WhatsApp Bot: token inválido', ['ip' => $request->ip()]);
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        $data = $request->all();
        $event = $data['event'] ?? ($data['type'] ?? '');

        // 3. Filtragem de eventos
        if (!in_array($event, ['messages.upsert', 'MESSAGES_UPSERT', 'message'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $waId = $this->extractWaId($data);
        $text = $this->extractText($data);

        if (!$waId || !$text || str_contains($waId, '@g.us')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $cleanPhone = preg_replace('/\D/', '', explode('@', $waId)[0]);
        if ($cleanPhone === $this->botPhone) {
            return response()->json(['status' => 'self'], 200);
        }

        // 4. Identificação do Usuário
        $user = $this->findUserByPhone($cleanPhone);

        if (!$user) {
            $this->sendDirect($waId, "❌ *Número não cadastrado no Vivensi.*\n\nPeça ao administrador para vincular seu WhatsApp ao sistema.");
            return response()->json(['status' => 'unauthorized'], 200);
        }

        // 5. Despacho Assíncrono (Melhoria de Performance)
        Log::info("WhatsApp Bot: Mensagem de [{$user->name}] enviada para fila.");
        
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
        return $data['data']['key']['remoteJid']
            ?? $data['data']['message']['key']['remoteJid']
            ?? $data['key']['remoteJid']
            ?? null;
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
