<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SystemSetting;

class IntegrationTestController extends Controller
{
    /**
     * Only super_admin users may access integration test endpoints.
     */
    public function __construct()
    {
        if (!app()->runningInConsole()) {
            $this->middleware(function ($request, $next) {
                if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
                    abort(403, 'Acesso restrito ao Super Admin.');
                }
                return $next($request);
            });
        }
    }

    /**
     * Test Gemini AI Integration
     */
    public function testGemini()
    {
        $apiKey = SystemSetting::getValue('gemini_api_key', env('GEMINI_API_KEY'));
        if (!$apiKey) {
            return response()->json(['error' => 'Chave GEMINI_API_KEY não configurada no painel Super Admin.'], 500);
        }

        try {
            // Using standard Google Generative AI REST endpoint to List Models
            // This is the safest way to verify the API Key without guessing the model version
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
            
            $response = Http::get($url);

            return response()->json([
                'service' => 'Google Gemini (ListModels Endpoint)',
                'status' => $response->status(),
                'successful' => $response->successful(),
                'message' => $response->successful() ? 'Conexão com a infraestrutura do Google Gemini estabelecida.' : 'Falha na validação da chave do Google.',
                'full_api_response' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'service' => 'Google Gemini',
                'status' => 500,
                'successful' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test DeepSeek AI Integration
     */
    public function testDeepSeek()
    {
        $apiKey = SystemSetting::getValue('deepseek_api_key', env('DEEPSEEK_API_KEY'));
        if (!$apiKey) {
            return response()->json(['error' => 'Chave DEEPSEEK_API_KEY não configurada no painel Super Admin.'], 500);
        }

        try {
            // Using DeepSeek standard chat completions endpoint (OpenAI compatible)
            // Endpoint de teste de conexao do Super Admin. Usa v4-flash para
            // ping rapido e barato. Substitui o alias 'deepseek-chat'
            // (deprecado em 2026/07/24 pela DeepSeek).
            $url = 'https://api.deepseek.com/chat/completions';

            $payload = [
                'model' => 'deepseek-v4-flash',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => 'Responda apenas com a frase: Hello World! Eu sou o DeepSeek e a conexão está perfeita.']
                ],
                'temperature' => 0.0,
                'max_tokens' => 50
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$apiKey}",
            ])->post($url, $payload);

            return response()->json([
                'service' => 'DeepSeek API',
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_text' => $response->successful() ? data_get($response->json(), 'choices.0.message.content') : null,
                'full_api_response' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'service' => 'DeepSeek',
                'status' => 500,
                'successful' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
