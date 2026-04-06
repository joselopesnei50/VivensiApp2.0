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

        // Buscar Contexto da ONG e do Projeto
        $tenant = \App\Models\Tenant::find($prospect->tenant_id);
        $project = \App\Models\Project::where('tenant_id', $prospect->tenant_id)
            ->where('status', 'active')
            ->latest()
            ->first() ?? \App\Models\Project::where('tenant_id', $prospect->tenant_id)->latest()->first();

        $ngoContext = $tenant ? "da ONG {$tenant->name}" : "da organização";
        $projectContext = $project ? "focada no projeto '{$project->name}' ({$project->description})" : "de impacto social";

        $prompt = "Você é um captador de recursos especializado {$ngoContext}. 
        Atualmente, você está trabalhando {$projectContext}.
        
        Analise o seguinte lead B2B para uma possível parceria ou patrocínio:
        Empresa: {$prospect->company_name}
        Categoria: {$prospect->category}
        Localização: {$prospect->address}
        Nota Google: {$prospect->google_rating}

        Objetivo: Identificar como a atividade desta empresa pode se alinhar aos objetivos do projeto citado e criar um pitch de venda curto, humano e persuasivo para WhatsApp. 
        O pitch NÃO deve mencionar o sistema Vivensi, mas sim o impacto social do projeto e o benefício da parceria para a empresa.
        
        Retorne estritamente um JSON no formato:
        {
            \"score\": 0-100,
            \"pain\": \"alinhamento estratégico detectado\",
            \"pitch\": \"texto para whatsapp (máximo 400 caracteres)\"
        }";

        // Lista de tentativas verificada diretamente via API ListModels
        $attempts = [
            ['ver' => 'v1beta', 'model' => 'models/gemini-flash-latest'], 
            ['ver' => 'v1beta', 'model' => 'models/gemini-2.0-flash'],
            ['ver' => 'v1beta', 'model' => 'models/gemini-pro-latest'],
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
