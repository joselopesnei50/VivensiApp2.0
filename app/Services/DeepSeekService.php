<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://api.deepseek.com/chat/completions';

    public function __construct(protected ?AiCallQuotaService $quota = null)
    {
        // Intentionally do not hit the database here.
        // Some Artisan commands may instantiate controllers/services without DB connectivity.
        $this->quota = $quota ?? app(AiCallQuotaService::class);
    }

    protected function resolveApiKey(): string
    {
        if ($this->apiKey) {
            return $this->apiKey;
        }

        $this->apiKey = trim((string) SystemSetting::getValue('deepseek_api_key'));
        return $this->apiKey;
    }

    /**
     * Envia mensagens ao DeepSeek V4.
     *
     * @param array        $messages Array de mensagens no formato OpenAI-compatible.
     * @param string|null  $model    Override do modelo. Default = 'deepseek-v4-flash'
     *                               (rápido e barato, ideal para chat conversacional).
     *                               Use 'deepseek-v4-pro' para análises estratégicas
     *                               profundas (Smart Analysis, propostas de edital,
     *                               estratégia de marketing).
     * @param int|null     $tenantId Se informado, consome cota diaria de IA do tenant.
     *                               Passe null apenas em contextos sem tenant (ex.: sandbox
     *                               super admin). Callers em contexto autenticado ou em job
     *                               por tenant DEVEM passar o tenant_id.
     *
     * Nota: os aliases 'deepseek-chat' e 'deepseek-reasoner' serão deprecados pela
     * DeepSeek em 2026/07/24. Por isso usamos sempre o nome explícito do modelo V4.
     *
     * Retornos de erro sao SEMPRE genericos ao caller — o corpo cru da API/exception
     * fica apenas no Log (proteção contra vazamento de detalhes internos ao frontend).
     */
    public function chat($messages, ?string $model = null, ?array $tools = null, ?int $tenantId = null)
    {
        $apiKey = $this->resolveApiKey();
        if (!$apiKey) {
            return [
                'error'      => 'Integração de IA não configurada. Solicite ao administrador.',
                'error_code' => 'ai_not_configured',
            ];
        }

        if ($tenantId !== null && !$this->quota->hasQuota($tenantId)) {
            Log::warning('DeepSeek: quota diaria de IA atingida', ['tenant_id' => $tenantId]);
            return [
                'error'      => 'Cota diária de IA atingida para este tenant. Tente novamente amanhã.',
                'error_code' => 'ai_quota_exceeded',
            ];
        }

        if ($tenantId !== null) {
            $this->quota->consume($tenantId);
        }

        try {
            $payload = [
                'model' => $model ?: 'deepseek-v4-flash',
                'messages' => $messages,
                'temperature' => 0.7,
            ];
            if (!empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            // retry sem throw: preserva a Response final pra diferenciar 401/5xx
            // do erro de conexao (sem isso, retry lanca RequestException e a
            // distincao UX de "chave invalida" vs "upstream down" some).
            $response = Http::timeout(60)->retry(2, 100, null, false)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('DeepSeek API erro', [
                'status'       => $response->status(),
                'body_preview' => mb_substr((string) $response->body(), 0, 500),
                'tenant_id'    => $tenantId,
            ]);

            if ($response->status() === 401) {
                return [
                    'error'      => 'Integração de IA com chave inválida. Solicite ao administrador atualizar em Configurações → Integrações.',
                    'error_code' => 'ai_invalid_key',
                ];
            }

            return [
                'error'      => 'A IA está indisponível no momento. Tente novamente em instantes.',
                'error_code' => 'ai_upstream_error',
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek conexao erro', [
                'message'   => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);
            return [
                'error'      => 'A IA está indisponível no momento. Tente novamente em instantes.',
                'error_code' => 'ai_connection_error',
            ];
        }
    }
}
