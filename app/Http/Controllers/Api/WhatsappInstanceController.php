<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
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
        $tenantId = auth()->user()->tenant_id;

        // Limite de 3 instâncias por tenant — verificação atômica com lock para evitar race condition
        $count = WhatsappInstance::forTenant($tenantId)->lockForUpdate()->count();
        abort_if($count >= 3, 422, 'Limite de 3 instâncias por conta atingido.');

        // Gerar nome único e token secreto para URL de webhook
        $instanceName = WhatsappInstance::generateInstanceName($tenantId);
        $instanceToken = Str::random(48);
        $number = $request->input('number');

        // Limpa cache de QR antigo (se houver tentativa anterior limpando registro órfão)
        \Illuminate\Support\Facades\Cache::forget('evo_qr_' . $instanceName);

        // Chamar a Evolution API para criar a instância
        $evo    = new EvolutionApiService();
        $result = $evo->createInstance($instanceName, $instanceToken, $number);

        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        // Salvar no banco local
        $instance = WhatsappInstance::create([
            'tenant_id'      => $tenantId,
            'instance_name'  => $instanceName,
            'instance_token' => $instanceToken,
            'status'         => 'connecting',
            'settings'       => $result['settings'] ?? [],
        ]);

        return response()->json([
            'instance'    => $instance,
            'pairingCode' => $result['qrcode']['pairingCode'] ?? ($result['pairingCode'] ?? null),
            'qrcode'      => $result['qrcode']['base64'] ?? ($result['base64'] ?? null),
        ]);
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
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $fetch['qrcode'], 40); // QR dura ~40s
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
     * Deleta (desconecta e remove) a instância.
     */
    public function destroy(int $id)
    {
        $instance = $this->findForTenant($id);

        // Desconectar da Evolution API antes de deletar
        $evo = new EvolutionApiService($instance);
        $evo->logout();

        $instance->delete(); // Soft delete

        return response()->json(['message' => 'Instância removida com sucesso.']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function findForTenant(int $id): WhatsappInstance
    {
        $tenantId = auth()->user()->tenant_id;
        return WhatsappInstance::forTenant($tenantId)->findOrFail($id);
    }
}
