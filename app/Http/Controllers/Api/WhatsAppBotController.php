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
        // Sem chave configurada o bot RECUSA tudo: ele identifica o usuário pelo número
        // que vem no payload, então aceitar sem token permitiria comandos forjados.
        $secret = config('services.whatsapp.bot_secret');
        if (!$secret) {
            Log::error('WhatsApp Bot: WHATSAPP_BOT_SECRET não configurado — requisição recusada.', ['ip' => $request->ip()]);
            return response()->json(['status' => 'not_configured'], 503);
        }
        $provided = $request->query('bot_token') ?? $request->header('X-Bot-Secret');
        if (!is_string($provided) || !hash_equals((string) $secret, $provided)) {
            Log::warning('WhatsApp Bot: token inválido', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $data = $request->all();
        $event = $data['event'] ?? ($data['type'] ?? '');

        // 3. Filtragem de eventos — Evolution manda muitos tipos (connection.
        // update, etc.), so processamos messages.
        if (!in_array($event, ['messages.upsert', 'MESSAGES_UPSERT', 'message'])) {
            return response()->json(['status' => 'ignored', 'reason' => 'event_type'], 200);
        }

        $waId = $this->extractWaId($data);
        $text = $this->extractText($data);
        $key  = $this->extractMessageKey($data);

        if (!$waId || !$text || str_contains($waId, '@g.us')) {
            return response()->json(['status' => 'ignored', 'reason' => 'payload'], 200);
        }

        $cleanPhone = preg_replace('/\D/', '', explode('@', $waId)[0]);
        if ($cleanPhone === $this->botPhone) {
            return response()->json(['status' => 'self'], 200);
        }

        // 4. Identificação do Usuário
        [$user, $ambiguous, $noAccess] = $this->findUserByPhone($cleanPhone);

        if ($ambiguous) {
            $this->sendDirect($waId, "⚠️ *Este número está em mais de uma conta do Vivensi.*\n\nPor segurança, o bot não sabe em qual painel gravar. Peça ao administrador para deixar este WhatsApp em uma conta só.");
            return response()->json(['status' => 'ambiguous'], 200);
        }

        if ($noAccess) {
            // Número cadastrado, mas a pessoa não tem um dos números do bot da
            // organização (1 incluso no plano + extras contratados).
            Log::info('WhatsApp Bot: usuario sem acesso ao bot', ['phone_suffix' => substr($cleanPhone, -4)]);
            $this->sendDirect($waId, "🔒 *Seu número está no Vivensi, mas ainda não tem acesso ao bot.*\n\nPeça ao administrador da organização para liberar em *Minha conta → Adicionais*.");
            return response()->json(['status' => 'no_bot_access'], 200);
        }

        if (!$user) {
            // Nao loga user_name — pode nao existir; ip ja sai por http log.
            Log::warning('WhatsApp Bot: user nao encontrado', [
                'phone_suffix' => substr($cleanPhone, -4),
            ]);
            $this->sendDirect($waId, "❌ *Número não cadastrado no Vivensi.*\n\nPeça ao administrador para vincular seu WhatsApp ao sistema.");
            return response()->json(['status' => 'unauthorized'], 200);
        }

        // 5. Despacho assincrono. Loga apenas user_id — nome/telefone sao PII.
        Log::info('WhatsApp Bot: mensagem despachada', ['user_id' => $user->id]);
        ProcessWhatsAppBotMessage::dispatch($user, $waId, $text, $key);

        return response()->json(['status' => 'queued'], 200);
    }

    /**
     * Extrai a chave completa da mensagem (id + remoteJid + fromMe).
     * Necessaria pra apagar a mensagem da Evolution API depois de processar,
     * garantindo que nenhum operador com acesso a instancia leia o conteudo
     * enviado pelo cliente.
     */
    private function extractMessageKey(array $data): array
    {
        $key = $data['data']['key']
            ?? $data['data']['message']['key']
            ?? $data['key']
            ?? [];

        return [
            'id'          => $key['id'] ?? null,
            'remoteJid'   => $key['remoteJid'] ?? null,
            'fromMe'      => (bool) ($key['fromMe'] ?? false),
            'participant' => $key['participant'] ?? null,
        ];
    }

    // ─── Auxiliares ───────────────────────────────────────────────────────────

    /**
     * Acha o usuário pelo telefone. Devolve [user|null, ambiguo, sem_acesso_ao_bot].
     *
     * Um número em mais de uma conta (ex.: a mesma pessoa em duas ONGs) é
     * ambíguo: antes o bot pegava a primeira em silêncio. A busca lenta
     * compara DDD + 8 últimos dígitos (antes só os 8 últimos, o que
     * confundia números iguais de DDDs diferentes). 2026-11-22.
     */
    private function findUserByPhone(string $phone): array
    {
        // NOTA 2026-08-17: users.phone eh AES-256 encrypted e phone_bidx eh
        // HMAC do valor exato cadastrado (com formatacao). REGEXP_REPLACE no
        // ciphertext nao funciona — busca correta eh via bidx.

        // 1. Tenta bidx pra formatos comuns do numero (fastpath, indice).
        $key   = (string) config('app.key');
        $bidxs = array_map(fn ($v) => hash_hmac('sha256', $v, $key), $this->phoneVariants($phone));
        $cols  = ['id', 'name', 'phone', 'phone_bidx', 'role', 'tenant_id', 'status', 'bot_access'];
        $matches = User::where('status', 'active')->whereIn('phone_bidx', $bidxs)->limit(20)->get($cols);

        // 2. Fallback lento: itera users ativos, descriptografa via accessor,
        // compara DDD + numero. O(n) mas ok pra bot interno (poucos users).
        $target = $this->phoneKey($phone);
        if ($target) {
            User::where('status', 'active')
                ->whereNotNull('phone')
                ->select($cols)
                ->chunk(500, function ($chunk) use ($target, &$matches) {
                    foreach ($chunk as $u) {
                        if ($matches->contains('id', $u->id)) continue;
                        $plain = $u->phone; // accessor descriptografa
                        if ($plain && $this->phoneKey((string) $plain) === $target) {
                            $matches->push($u);
                        }
                    }
                });
        }

        // 3. Só vale quem tem um dos números do bot da organização (adicionais,
        // 2026-11-25). Admin da plataforma sempre pode. Isso também desfaz a
        // ambiguidade quando o número está em duas contas e só uma liberou.
        $allowed = $matches->filter(fn ($u) => $u->role === 'super_admin' || (bool) $u->bot_access)->values();

        if ($allowed->count() === 1) {
            // Recarrega com todas as colunas (o select acima e parcial).
            return [User::find($allowed->first()->id), false, false];
        }
        if ($allowed->count() > 1) {
            $this->logAmbiguous($phone, $allowed->count());
            return [null, true, false];
        }
        if ($matches->isNotEmpty()) {
            return [null, false, true];
        }

        return [null, false, false];
    }

    /**
     * Chave de comparação: DDD + 8 últimos dígitos (cobre com/sem DDI 55 e
     * com/sem o 9 do celular, sem confundir DDDs diferentes).
     */
    private function phoneKey(string $phone): ?string
    {
        $d = preg_replace('/\D/', '', $phone);
        if (str_starts_with($d, '55') && strlen($d) >= 12) {
            $d = substr($d, 2);
        }
        if (strlen($d) < 10 || strlen($d) > 11) {
            return null;
        }
        return substr($d, 0, 2) . substr($d, -8);
    }

    private function logAmbiguous(string $phone, int $count): void
    {
        Log::warning('WhatsApp Bot: telefone ambiguo entre multiplos users', [
            'phone_suffix' => substr($phone, -4),
            'match_count'  => $count,
        ]);
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
