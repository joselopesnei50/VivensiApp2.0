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

    public function chat($messages)
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
                'model' => 'deepseek-chat',
                'messages' => $messages,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Erro API DeepSeek: ' . $response->body());
            return ['error' => 'Erro na API DeepSeek: ' . $response->body()];

        } catch (\Exception $e) {
            \Log::error('Erro Conexao DeepSeek: ' . $e->getMessage());
            return ['error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
}
