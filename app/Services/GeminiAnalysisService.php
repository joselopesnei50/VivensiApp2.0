<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

class GeminiAnalysisService {
    public function analyze($prospect) {
        $apiKey = SystemSetting::getValue('gemini_api_key');

        if (!$apiKey) {
            throw new \Exception('Chave da API Gemini não configurada.');
        }

        $prompt = "Analise o seguinte lead B2B para o sistema de gestão Vivensi:
        Empresa: {$prospect->company_name}
        Categoria: {$prospect->category}
        Localização: {$prospect->address}
        Nota Google: {$prospect->google_rating}

        Objetivo: Identificar uma 'dor' (problema) que o Vivensi resolve para esse tipo de negócio e criar um pitch de venda curto e persuasivo para WhatsApp.
        
        Retorne estritamente um JSON no formato:
        {
            \"score\": 0-100,
            \"pain\": \"problema detectado\",
            \"pitch\": \"texto para whatsapp\"
        }";

        // Lista de tentativas (Fallback) - Adicionado prefixo 'models/' conforme orientação
        $attempts = [
            ['ver' => 'v1beta', 'model' => 'models/gemini-1.5-flash'], 
            ['ver' => 'v1beta', 'model' => 'models/gemini-1.5-pro'],
            ['ver' => 'v1', 'model' => 'models/gemini-pro'],
        ];

        $response = null;
        $lastError = '';

        foreach ($attempts as $attempt) {
            // A URL agora usa o modelo com o prefixo correto
            $url = "https://generativelanguage.googleapis.com/{$attempt['ver']}/{$attempt['model']}:generateContent?key=" . $apiKey;
            
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

                if ($response->successful()) {
                    break; // Sucesso!
                }
                $lastError = $response->body();
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        if (!$response || !$response->successful()) {
            Log::error('Falha Total Gemini Fallback: ' . $lastError);
            throw new \Exception('Bruce AI está offline. Verifique sua chave API no Super Admin. Detalhe: ' . $lastError);
        }

        $body = $response->json();
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Resposta Bruce AI inválida.');
        }

        $resText = $body['candidates'][0]['content']['parts'][0]['text'];
        $res = json_decode($resText, true);
        
        if (!$res) {
            $cleanJson = preg_replace('/^```json\s*|\s*```$/i', '', trim($resText));
            $res = json_decode($cleanJson, true);
        }

        if ($res) {
            $prospect->update([
                'lead_score' => $res['score'] ?? 0,
                'ai_analysis' => $res['pain'] ?? 'Análise automática indisponível.',
                'personalized_pitch' => $res['pitch'] ?? null,
                'status' => 'analyzed'
            ]);
        }

        return $res;
    }
}
