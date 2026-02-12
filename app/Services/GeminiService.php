<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected ?string $apiKey = null;
    // NOTE: gemini-pro is deprecated; use current v1beta REST endpoint + stable model.
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

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

        $this->apiKey = trim((string) SystemSetting::getValue('gemini_api_key'));
        return $this->apiKey;
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
        $apiKey = $this->resolveApiKey();
        if (!$apiKey) {
            return ['error' => 'Chave da API Gemini não configurada no Painel Admin.'];
        }

        try {
            $response = Http::timeout(60)->retry(2, 200)->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl, [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Erro API Gemini', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['error' => 'Erro na API Gemini.'];

        } catch (\Exception $e) {
            \Log::error('Erro Conexao Gemini: ' . $e->getMessage());
            return ['error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }

    public function generateText($prompt)
    {
        $result = $this->callGemini([
            ['text' => $prompt]
        ]);

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }

        return null;
    }
}
