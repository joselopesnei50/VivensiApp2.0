<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsappInstanceController (API)
 * 
 * Gerencia o ciclo de vida das instâncias da Evolution API por tenant:
 * criar, conectar (QR/Pairing), verificar status e deletar.
 */
class WhatsappInstanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            abort_unless(
                in_array($user->role, ['manager', 'ngo', 'super_admin'], true),
                403,
                'Acesso restrito.'
            );
            return $next($request);
        });
    }

    /**
     * Lista instâncias do tenant autenticado.
     */
    public function index()
    {
        $tenantId  = auth()->user()->tenant_id;
        $instances = WhatsappInstance::forTenant($tenantId)->get();

        return response()->json($instances);
    }

    /**
     * Cria e provisiona uma nova instância na Evolution API.
     */
    public function store(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id; // Para super_admin = 2 (tenant da plataforma)

        try {
            // ── Conta instâncias ativas (sem soft-deleted)
            $count = \Illuminate\Support\Facades\DB::table('whatsapp_instances')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count();

            if ($count >= 3) {
                return response()->json([
                    'error' => 'Limite de 3 instâncias atingido. Delete uma instância existente antes de criar outra.',
                ], 422);
            }

            // Gera nome único
            $instanceName  = 'vivensi_t' . ($tenantId ?? 'sa') . '_' . Str::random(6);
            $instanceToken = Str::random(48);
            $number        = $request->input('number');

            \Illuminate\Support\Facades\Cache::forget('evo_qr_' . $instanceName);

            // ── Verifica se a chave da Evolution API está configurada
            $globalKey = config('whatsapp.evolution_global_key');
            if (!$globalKey) {
                \Illuminate\Support\Facades\Log::error('EVO store: EVOLUTION_GLOBAL_KEY não configurada no .env');
                return response()->json([
                    'error'   => 'Chave da Evolution API não configurada.',
                    'details' => 'Configure EVOLUTION_GLOBAL_KEY no arquivo .env do servidor.',
                ], 422);
            }

            // ── Chama a Evolution API
            $evo    = new EvolutionApiService();
            $result = $evo->createInstance($instanceName, $instanceToken, $number);

            if (isset($result['error'])) {
                \Illuminate\Support\Facades\Log::error('EVO store: falha ao criar instância', [
                    'tenant_id' => $tenantId,
                    'user_id'   => $user->id,
                    'instance'  => $instanceName,
                    'error'     => $result['error'],
                    'details'   => $result['details'] ?? null,
                ]);
                return response()->json([
                    'error' => 'Falha ao provisionar instância. Tente novamente em alguns minutos.',
                ], 422);
            }

            // Valida resposta mínima da Evolution API
            if (empty($result) || (!isset($result['instance']) && !isset($result['hash']))) {
                \Illuminate\Support\Facades\Log::error('EVO store: resposta inesperada', [
                    'tenant_id' => $tenantId,
                    'instance'  => $instanceName,
                    'result'    => $result,
                ]);
                return response()->json([
                    'error' => 'Falha ao conectar com o serviço. Contate o suporte se o problema persistir.',
                ], 422);
            }

            // ── Salva no banco
            $settings = $result['settings'] ?? [];
            if (!is_array($settings)) {
                $settings = [];
            }

            $instance = WhatsappInstance::create([
                'tenant_id'      => $tenantId,
                'instance_name'  => $instanceName,
                'instance_token' => $instanceToken,
                'status'         => 'connecting',
                'settings'       => $settings,
            ]);

            // Ativa o warming progressivo (14 dias) — toda instancia nova
            // comeca em 20 msgs/dia e cresce gradualmente ate 370/dia. Sem isso,
            // a instancia tentaria usar o daily_limit total no dia 1 e a Meta
            // bania pela curva de uso suspeita.
            try {
                (new AntiBanManager(new EvolutionApiService($instance)))
                    ->startWarming($instance);
            } catch (\Throwable $e) {
                // Warming e best-effort — falha aqui nao bloqueia a criacao
                // da instancia. Loga para investigacao posterior.
                Log::warning('WhatsappInstance store: falha ao iniciar warming', [
                    'instance_id' => $instance->id,
                    'error'       => $e->getMessage(),
                ]);
            }

            return response()->json([
                'instance'    => $instance->fresh(),
                'pairingCode' => $result['qrcode']['pairingCode'] ?? ($result['pairingCode'] ?? null),
                'qrcode'      => $result['qrcode']['base64'] ?? ($result['base64'] ?? null),
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('EVO store: exception inesperada', [
                'tenant_id' => $tenantId,
                'user_id'   => $user->id ?? null,
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            return response()->json([
                'error' => 'Erro interno ao criar instância. Verifique os logs do servidor.',
            ], 500);
        }
    }

    /**
     * Retorna o status atual da conexão da instância.
     */
    public function status(int $id)
    {
        $instance = $this->findForTenant($id);

        $evo    = new EvolutionApiService($instance);
        $result = $evo->getConnectionState();

        // Sincronizar status no banco
        $state = $result['instance']['state'] ?? null;
        if ($state) {
            $instance->update(['status' => in_array($state, ['open', 'connecting', 'close']) ? $state : 'close']);
        }

        return response()->json([
            'instance' => $instance->fresh(),
            'evo'      => $result,
        ]);
    }

    /**
     * Gera QR Code ou Pairing Code para conectar o WhatsApp.
     */
    public function connect(Request $request, int $id)
    {
        $instance = $this->findForTenant($id);
        $evo = new EvolutionApiService($instance);

        // Se o banco já marcou como conectado (via webhook), retorna imediatamente
        if ($instance->status === 'open') {
            return response()->json(['status' => 'open', 'state' => 'open']);
        }

        // Banco ainda não atualizou — consulta a Evolution API diretamente como fallback
        $liveState = $evo->getConnectionState();
        $liveStatus = $liveState['instance']['state'] ?? null;
        if ($liveStatus && in_array($liveStatus, ['open', 'connecting', 'close'], true)) {
            $instance->update(['status' => $liveStatus === 'close' ? 'close' : $liveStatus]);
            $instance->refresh();
        }

        // Segunda verificação após sincronizar com a Evolution API
        if ($instance->status === 'open') {
            return response()->json(['status' => 'open', 'state' => 'open']);
        }

        if ($request->filled('phone')) {
            // Pairing Code (v2 com busca ativa se necessário)
            $phone  = preg_replace('/\D/', '', $request->input('phone'));
            $result = $evo->getPairingCode($phone);
        } else {
            // QR Code: Tenta o cache primeiro (populado pelo Webhook)
            $cacheKey = 'evo_qr_' . $instance->instance_name;
            $cachedQr = \Illuminate\Support\Facades\Cache::get($cacheKey);
            
            if ($cachedQr) {
                $result = ['base64' => $cachedQr, 'status' => $instance->status];
            } else {
                // FALLBACK: Busca ATIVA na Evolution API (sem esperar pelo Webhook)
                $fetch = $evo->fetchConnectionCode($instance->instance_name);
                
                if (isset($fetch['qrcode']) && $fetch['qrcode']) {
                    // Popula o cache para os próximos polls serem instantâneos
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $fetch['qrcode'], 120); // QR válido por ~2 minutos
                    $result = ['base64' => $fetch['qrcode'], 'status' => $fetch['status'] ?? $instance->status];
                } else {
                    $result = [
                        'error'   => $fetch['error'] ?? 'Gerando QR Code... aguarde 2-5 segundos.', 
                        'status'  => $fetch['status'] ?? 'generating',
                        'details' => $fetch['details'] ?? null
                    ];
                }
            }
        }

        return response()->json($result);
    }

    /**
     * Salva (ou remove) o proxy desta instância.
     * Aceita http://, https://, socks5://, socks5h:// — ou string vazia para remover.
     */
    public function updateProxy(Request $request, int $id)
    {
        $instance = $this->findForTenant($id);

        $proxyUrl = trim($request->input('proxy_url', ''));

        if ($proxyUrl !== '') {
            if (!preg_match('#^(https?|socks5h?)://\S+#i', $proxyUrl)) {
                return response()->json(['error' => 'Formato inválido. Use http://, https://, socks5:// ou socks5h://.'], 422);
            }
            if (strlen($proxyUrl) > 512) {
                return response()->json(['error' => 'URL do proxy muito longa (máx 512 chars).'], 422);
            }
            // SSRF guard: bloqueia IPs privados, loopback e metadata endpoints
            if ($this->isPrivateOrMetadataHost($proxyUrl)) {
                return response()->json(['error' => 'O proxy não pode apontar para redes internas ou endpoints de metadata.'], 422);
            }
        }

        $settings = $instance->settings ?? [];

        if ($proxyUrl === '') {
            unset($settings['proxy_url']);
        } else {
            $settings['proxy_url'] = $proxyUrl;
        }

        $instance->update(['settings' => $settings]);

        return response()->json([
            'message'    => $proxyUrl === '' ? 'Proxy removido.' : 'Proxy salvo.',
            'proxy_set'  => $proxyUrl !== '',
        ]);
    }

    private function isPrivateOrMetadataHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return true;

        // Remove brackets IPv6 (e.g. [::1])
        $host = trim($host, '[]');

        // Resolve hostname para IP (recusa se não resolver)
        $ip = @gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // Não resolveu e não é IP literal — recusa por segurança
            return true;
        }

        // Valida que é um IP válido
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;

        // Bloqueia ranges privados, loopback e link-local (incl. AWS metadata)
        $blockedRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16', // link-local + AWS instance metadata
            '100.64.0.0/10',  // Carrier-grade NAT
            '0.0.0.0/8',
        ];

        $ipLong = ip2long($ip);
        if ($ipLong === false) return true;

        foreach ($blockedRanges as $range) {
            [$subnet, $bits] = explode('/', $range);
            $mask = -1 << (32 - (int) $bits);
            if (($ipLong & $mask) === (ip2long($subnet) & $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deleta (desconecta e remove) a instância.
     */
    public function destroy(int $id)
    {
        $instance = $this->findForTenant($id);

        // Remove da Evolution API (logout + delete) — erros são ignorados intencionalmente
        // para não bloquear a exclusão local caso a API esteja inacessível
        $evo = new EvolutionApiService($instance);
        $evo->logout();
        $evo->deleteInstance();

        $instance->delete(); // Soft delete

        return response()->json(['message' => 'Instância removida com sucesso.']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function findForTenant(int $id): WhatsappInstance
    {
        $tenantId = auth()->user()->tenant_id; // NULL for super_admin
        $query = WhatsappInstance::withoutGlobalScopes();
        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }
        return $query->findOrFail($id);
    }
}
