<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = trim(SystemSetting::getValue('gemini_api_key'));
    }

    public function analyzePdfText($text, $prompt)
    {
        return $this->callGemini([
            ['text' => $prompt . "\n\nConteúdo do Texto:\n" . $text]
        ]);
    }

    public function analyzePdfFile($base64Data, $prompt)
    {
        return $this->callGemini([
            ['text' => $prompt],
            [
                'inline_data' => [
                    'mime_type' => 'application/pdf',
                    'data' => $base64Data
                ]
            ]
        ]);
    }

    public function callGemini($parts)
    {
        if (!$this->apiKey) {
            return ['error' => 'Chave da API Gemini não configurada no Painel Admin.'];
        }

        try {
            $response = Http::withoutVerifying()->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Erro API Gemini: ' . $response->body());
            return ['error' => 'Erro na API Gemini: ' . $response->body()];

        } catch (\Exception $e) {
            \Log::error('Erro Conexao Gemini: ' . $e->getMessage());
            return ['error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
}
