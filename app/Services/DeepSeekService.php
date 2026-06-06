<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class DeepSeekService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://api.deepseek.com/chat/completions';

    public function __construct()
    {
        // Intentionally do not hit the database here.
        // Some Artisan commands may instantiate controllers/services without DB connectivity.
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
     *
     * Nota: os aliases 'deepseek-chat' e 'deepseek-reasoner' serão deprecados pela
     * DeepSeek em 2026/07/24. Por isso usamos sempre o nome explícito do modelo V4.
     */
    public function chat($messages, ?string $model = null)
    {
        $apiKey = $this->resolveApiKey();
        if (!$apiKey) {
            return ['error' => 'Chave da API DeepSeek não configurada no Painel Admin.'];
        }

        try {
            $response = Http::timeout(60)->retry(2)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => $model ?: 'deepseek-v4-flash',
                'messages' => $messages,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Erro API DeepSeek: ' . $response->body());

            if ($response->status() === 401) {
                return ['error' => 'Chave DeepSeek inválida ou expirada. Peça ao administrador para atualizar a chave no Painel Admin (Configurações → Integrações).'];
            }

            return ['error' => 'Erro na API DeepSeek: ' . $response->body()];

        } catch (\Exception $e) {
            \Log::error('Erro Conexao DeepSeek: ' . $e->getMessage());
            return ['error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
}
