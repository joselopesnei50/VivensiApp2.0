<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SystemSetting;

class IntegrationTestController extends Controller
{
    /**
     * Middleware check for simple token authentication
     */
    public function __construct(Request $request)
    {
        // Simple security: only allow requests with ?token=vivensi_test
        if ($request->query('token') !== 'vivensi_test') {
            abort(403, 'Unauthorized access to integration tests.');
        }
    }

    /**
     * Test PagSeguro Integration
     */
    public function testPagSeguro()
    {
        $token = SystemSetting::getValue('pagseguro_token', env('PAGSEGURO_TOKEN'));
        $env = SystemSetting::getValue('pagseguro_environment', env('PAGSEGURO_ENV', 'sandbox'));
        $baseUrl = $env === 'production' ? 'https://api.pagseguro.com' : 'https://sandbox.api.pagseguro.com';

        if (!$token) {
            return response()->json(['error' => 'PAGSEGURO_TOKEN não configurado no painel Admin.'], 500);
        }

        try {
            // Simple GET request to list orders to verify auth
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ])->get("{$baseUrl}/orders");

            // PagSeguro might return 400 for structural validations, but 401 means auth failed.
            $isAuthSuccess = !in_array($response->status(), [401, 403]);

            return response()->json([
                'service' => 'PagSeguro',
                'environment' => $env,
                'status' => $response->status(),
                'successful' => $isAuthSuccess,
                'message' => $isAuthSuccess ? 'Token Válido! Conexão com PagSeguro estabelecida com sucesso.' : 'Falha na autenticação.',
                'api_response' => $response->json(),
                'masked_token' => substr($token, 0, 8) . '...' . substr($token, -4)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'service' => 'PagSeguro',
                'status' => 500,
                'successful' => false,
                'error' => $e->getMessage()
            ], 500);
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
            $url = 'https://api.deepseek.com/chat/completions';
            
            $payload = [
                'model' => 'deepseek-chat',
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
