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
        $this->registerPhoneNumber($phoneNumberId, $accessToken, $registrationPin);
        $this->subscribeAppToWaba($wabaId, $accessToken);

        return $this->persistInstance($tenantId, $wabaId, $phoneNumberId, $accessToken);
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

        if (!$response->successful()) {
            Log::error('CloudApi Onboarding: /register falhou', [
                'phone_number_id' => $phoneNumberId,
                'body'            => $response->body(),
            ]);
            throw new RuntimeException('Falha ao registrar phone_number_id na Meta Cloud API.');
        }
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
