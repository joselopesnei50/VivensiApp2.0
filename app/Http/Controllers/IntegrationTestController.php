<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $token = env('PAGSEGURO_TOKEN');
        $env = env('PAGSEGURO_ENV', 'sandbox');
        $baseUrl = $env === 'production' ? 'https://api.pagseguro.com' : 'https://sandbox.api.pagseguro.com';

        try {
            // Simple GET request to list orders to verify auth
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ])->get("{$baseUrl}/orders", [
                'reference_id' => 'VIVENSI_TEST_CONNECTION'
            ]);

            return response()->json([
                'service' => 'PagSeguro',
                'environment' => $env,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'message' => $response->successful() ? 'Conexão com PagSeguro estabelecida com sucesso!' : 'Falha na autenticação ou erro na API.',
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
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY não configurada no .env ou aws_env.txt'], 500);
        }

        try {
            // Using standard Google Generative AI REST endpoint
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
            
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Responda com apenas uma frase curta se você consegue me ouvir. Diga seu nome como assistente.']
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            return response()->json([
                'service' => 'Google Gemini (1.5 Flash)',
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_text' => $response->successful() ? data_get($response->json(), 'candidates.0.content.parts.0.text') : null,
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
        $apiKey = env('DEEPSEEK_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'DEEPSEEK_API_KEY não configurada no .env ou aws_env.txt'], 500);
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
