<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class DeepSeekService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.deepseek.com/chat/completions';

    public function __construct()
    {
        $this->apiKey = trim(SystemSetting::getValue('deepseek_api_key'));
    }

    public function chat($messages)
    {
        if (!$this->apiKey) {
            return ['error' => 'Chave da API DeepSeek não configurada no Painel Admin.'];
        }

        try {
            $response = Http::withoutVerifying()->timeout(60)->retry(2)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
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
