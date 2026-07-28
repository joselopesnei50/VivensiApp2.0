<?php

namespace App\Services\WhatsApp;

use App\Models\SystemSetting;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orquestração do Embedded Signup do Meta WhatsApp Business Cloud API.
 *
 * Fluxo (executado do lado backend após o JS SDK devolver o `code`):
 *   1. Troca code por access_token (short-lived)
 *   2. Registra o número (POST /{phone_number_id}/register) — obrigatório antes de enviar/receber
 *   3. Inscreve o app à WABA (POST /{waba_id}/subscribed_apps) — habilita webhooks
 *   4. Cria WhatsappInstance com provider=cloud_api + credenciais
 *
 * Config lida do SystemSetting:
 *   - meta_cloud_app_id     (público, também usado no JS)
 *   - meta_cloud_app_secret (privado, usado só server-side)
 *   - meta_cloud_config_id  (público — Login Configuration da Meta)
 *
 * Referência: https://developers.facebook.com/docs/whatsapp/embedded-signup/
 */
class CloudApiOnboardingService
{
    private string $apiVersion;
    private string $appId;
    private string $appSecret;

    public function __construct()
    {
        $this->apiVersion = config('whatsapp.meta_api_version', 'v22.0');
        $this->appId      = (string) SystemSetting::getValue('meta_cloud_app_id', '');
        $this->appSecret  = (string) SystemSetting::getValue('meta_cloud_app_secret', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret);
    }

    /**
     * Executa o fluxo completo do Embedded Signup.
     *
     * @param  int    $tenantId       Tenant que está conectando
     * @param  string $code           Authorization code recebido do FB.login
     * @param  string $wabaId         WhatsApp Business Account ID vindo do fbe_object
     * @param  string $phoneNumberId  Phone Number ID vindo do fbe_object
     * @param  string $registrationPin  PIN de 6 dígitos escolhido pelo tenant (obrigatório em /register)
     * @return WhatsappInstance   Instância recém-criada, provider=cloud_api
     * @throws RuntimeException  Se qualquer etapa falhar (com detalhes no log)
     */
    public function completeSignup(int $tenantId, string $code, string $wabaId, string $phoneNumberId, string $registrationPin): WhatsappInstance
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Credenciais Meta Cloud (app_id/app_secret) não configuradas no sistema.');
        }

        $accessToken = $this->exchangeCodeForToken($code);
        $this->inspectToken($accessToken);
        $this->registerPhoneNumber($phoneNumberId, $accessToken, $registrationPin);
        $this->subscribeAppToWaba($wabaId, $accessToken);

        return $this->persistInstance($tenantId, $wabaId, $phoneNumberId, $accessToken);
    }

    /**
     * Onboarding manual/assistido — cliente já criou o WABA no Business
     * Manager, gerou um System User Access Token e cola as 3 credenciais
     * no Vivensi. Não passa por FB.login: valida direto via Graph API,
     * inscreve app na WABA e persiste a instance.
     *
     * @param  int     $tenantId       Tenant que está conectando (auth user)
     * @param  string  $wabaId         WABA ID copiado pelo cliente
     * @param  string  $phoneNumberId  Phone Number ID copiado pelo cliente
     * @param  string  $accessToken    System User Access Token gerado pelo cliente
     * @param  ?string $registrationPin  PIN opcional — só usar se o cliente ainda
     *                                   NÃO registrou o número no Business Manager
     * @throws RuntimeException  Se validação falhar ou WABA já pertencer a outro tenant
     */
    public function completeManualSignup(
        int $tenantId,
        string $wabaId,
        string $phoneNumberId,
        string $accessToken,
        ?string $registrationPin = null,
    ): WhatsappInstance {
        // Passo 1 — sanidade: valida token contra a WABA informada.
        // Se retornar 200 com id igual ao wabaId, sabemos que:
        //   (a) o token é válido
        //   (b) o token tem escopo/permissão suficiente pra ler essa WABA
        //   (c) o wabaId realmente existe
        $probe = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/{$this->apiVersion}/{$wabaId}", [
                'fields' => 'id,name,currency,timezone_id',
            ]);

        if (!$probe->successful()) {
            $err     = (array) $probe->json('error', []);
            $errMsg  = trim((string) ($err['message'] ?? ''));
            $errCode = isset($err['code']) ? (int) $err['code'] : null;

            Log::warning('CloudApi Manual Signup: validação da WABA falhou', [
                'status'  => $probe->status(),
                'code'    => $errCode,
                'message' => $errMsg,
            ]);

            $friendly = match ($errCode) {
                190     => 'Token inválido ou expirado. Gere um novo System User Access Token no Business Manager e cole aqui.',
                100     => 'WABA ID não encontrado ou o token não tem acesso a essa WABA. Confira o ID no Business Manager.',
                default => 'Não foi possível validar suas credenciais na Meta.'
                    . ($errMsg !== '' ? " Detalhe: {$errMsg}" : ''),
            };

            throw new RuntimeException($friendly);
        }

        // Passo 2 — diagnóstico do tipo de token (não bloqueia).
        $this->inspectToken($accessToken);

        // Passo 3 — se o cliente forneceu PIN, tenta registrar o número.
        // Sem PIN, assumimos que o cliente já registrou no Business Manager;
        // se ele não registrou, o envio real vai falhar com error code claro.
        if ($registrationPin !== null && $registrationPin !== '') {
            $this->registerPhoneNumber($phoneNumberId, $accessToken, $registrationPin);
        }

        // Passo 4 — inscreve nosso app na WABA (webhook começa a fluir).
        $this->subscribeAppToWaba($wabaId, $accessToken);

        // Passo 5 — proteção cross-tenant: se já existe instance com esse
        // phone_number_id em OUTRO tenant, bloqueia (evita hijack de número).
        $conflict = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('phone_number_id', $phoneNumberId)
            ->where('tenant_id', '!=', $tenantId)
            ->first();

        if ($conflict) {
            Log::warning('CloudApi Manual Signup: conflito de phone_number_id em outro tenant', [
                'phone_number_id'       => $phoneNumberId,
                'requesting_tenant_id'  => $tenantId,
                'existing_tenant_id'    => $conflict->tenant_id,
            ]);
            throw new RuntimeException('Este número WhatsApp já está conectado a outra conta Vivensi. Se você é o dono, contate o suporte.');
        }

        return $this->persistInstance($tenantId, $wabaId, $phoneNumberId, $accessToken);
    }

    /**
     * Diagnóstico: chama /debug_token pra saber se o token que recebemos é do
     * tipo esperado (System-user, sem expiração) ou algo mais fraco (User
     * access token, expira em ~1h). Só loga — não bloqueia o fluxo.
     *
     * Referência: https://developers.facebook.com/docs/graph-api/reference/debug_token/
     */
    private function inspectToken(string $token): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/debug_token", [
                'input_token'  => $token,
                'access_token' => $this->appId . '|' . $this->appSecret,
            ]);

            if (!$response->successful()) {
                Log::warning('CloudApi Onboarding: debug_token retornou erro (não bloqueia signup)', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }

            $data = (array) $response->json('data', []);

            Log::info('CloudApi Onboarding: token inspecionado', [
                'type'                    => $data['type']                    ?? null,
                'expires_at'              => $data['expires_at']              ?? null,
                'data_access_expires_at'  => $data['data_access_expires_at']  ?? null,
            ]);

            if (!empty($data['expires_at']) && (int) $data['expires_at'] > 0) {
                Log::warning('CloudApi Onboarding: token com expiração — verifique se a Configuration no painel Meta usa System-user access token');
            }

            return $data;
        } catch (\Throwable $e) {
            Log::warning('CloudApi Onboarding: debug_token exception (não bloqueia signup)', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Etapa 1 — troca authorization code por access_token.
     */
    public function exchangeCodeForToken(string $code): string
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'code'          => $code,
        ]);

        if (!$response->successful()) {
            Log::error('CloudApi Onboarding: exchange code falhou', ['body' => $response->body()]);
            throw new RuntimeException('Falha ao trocar authorization code por access_token.');
        }

        $token = $response->json('access_token');
        if (empty($token)) {
            throw new RuntimeException('Resposta da Meta sem access_token.');
        }

        return $token;
    }

    /**
     * Etapa 2 — registra o phone_number_id na Cloud API (POST /register).
     * Sem este passo o número não pode enviar nem receber mensagens.
     */
    public function registerPhoneNumber(string $phoneNumberId, string $accessToken, string $pin): void
    {
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$phoneNumberId}/register", [
                'messaging_product' => 'whatsapp',
                'pin'               => $pin,
            ]);

        if ($response->successful()) {
            return;
        }

        // Loga o corpo da Meta pra debug (não inclui o access_token, só body do erro).
        Log::error('CloudApi Onboarding: /register falhou', [
            'phone_number_id' => $phoneNumberId,
            'body'            => $response->body(),
        ]);

        $err     = (array) $response->json('error', []);
        $errCode = isset($err['code']) ? (int) $err['code'] : null;
        $errMsg  = (string) ($err['message'] ?? '');

        // Caso mais comum na prática: número já tem verificação em duas etapas
        // ligada com PIN diferente do informado. UX exige mensagem acionável.
        if ($errCode === 133005) {
            throw new RuntimeException('PIN incorreto: este número já possui verificação em duas etapas com outro PIN. Use o PIN existente ou redefina-o no WhatsApp Business Manager.');
        }

        // Demais casos: expõe code + message da Meta pra o usuário (nunca token).
        $suffix = $errCode !== null || $errMsg !== ''
            ? sprintf(' (Meta code=%s: %s)', $errCode ?? '?', mb_substr($errMsg, 0, 300))
            : '';

        throw new RuntimeException('Falha ao registrar phone_number_id na Meta Cloud API.' . $suffix);
    }

    /**
     * Etapa 3 — inscreve nosso app à WABA (POST /{waba_id}/subscribed_apps).
     * Sem este passo o webhook não recebe mensagens.
     */
    public function subscribeAppToWaba(string $wabaId, string $accessToken): void
    {
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$wabaId}/subscribed_apps");

        if (!$response->successful()) {
            Log::error('CloudApi Onboarding: subscribed_apps falhou', [
                'waba_id' => $wabaId,
                'body'    => $response->body(),
            ]);
            throw new RuntimeException('Falha ao inscrever aplicativo à WABA.');
        }
    }

    /**
     * Etapa 4 — persiste WhatsappInstance com provider=cloud_api.
     * Se já existe instância pra esse phone_number_id + tenant, atualiza credenciais.
     */
    public function persistInstance(int $tenantId, string $wabaId, string $phoneNumberId, string $accessToken): WhatsappInstance
    {
        return WhatsappInstance::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $tenantId, 'phone_number_id' => $phoneNumberId],
            [
                'provider'           => WhatsappInstance::PROVIDER_CLOUD_API,
                'waba_id'            => $wabaId,
                'graph_access_token' => $accessToken,
                'instance_name'      => 'cloud_' . $tenantId . '_' . substr($phoneNumberId, -6),
                'instance_token'     => bin2hex(random_bytes(32)), // Placeholder — Cloud API não usa
                'status'             => 'open',
            ]
        );
    }
}
