<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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
            \"score\": 0-100 (probabilidade de conversão),
            \"pain\": \"uma frase sobre o problema detectado\",
            \"pitch\": \"texto curto para abordagem no whatsapp iniciando com 'Olá, notei que a [Empresa]...'\"
        }";

        $response = Http::post("https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Erro no Gemini: ' . $response->body());
        }

        $body = $response->json();
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Resposta inválida do Gemini: ' . json_encode($body));
        }

        $resText = $body['candidates'][0]['content']['parts'][0]['text'];
        $res = json_decode($resText, true);
        
        if (!$res) {
            // Em caso de erro no JSON retornado, tentar limpar possíveis markdown blocks
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
