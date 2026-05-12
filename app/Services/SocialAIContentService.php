<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\AiImageUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SocialAIContentService
{
    protected $deepseekKey;
    protected $togetherKey;

    public function __construct()
    {
        $this->deepseekKey = SystemSetting::getValue('deepseek_api_key');
        $this->togetherKey = SystemSetting::getValue('together_ai_api_key');
    }

    /**
     * Valida se o usuário ainda possui cota para o mês atual.
     */
    public function validateQuota(int $userId)
    {
        $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
        if ($currentUsage >= 60) {
            throw new Exception("Limite mensal de 60 imagens atingido. Sua cota será renovada no próximo mês.");
        }
    }

    /**
     * Gera o texto do post e o prompt da imagem usando DeepSeek.
     */
    public function generateContent(string $theme): array
    {
        if (!$this->deepseekKey) {
            throw new Exception("DeepSeek API Key não configurada no sistema.");
        }

        $prompt = "Atue como um especialista em marketing digital. Crie um post para rede social sobre o tema: '{$theme}'.
        Retorne OBRIGATORIAMENTE um JSON com os seguintes campos:
        'caption': A legenda do post em português, persuasiva e com emojis.
        'image_prompt': Um prompt descritivo detalhado em INGLÊS para gerar uma imagem fotorrealista de alta qualidade sobre este tema.
        Responda apenas o JSON puro, sem blocos de código markdown.";

        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $this->deepseekKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (!$response->successful()) {
            Log::error("DeepSeek API Error", ['response' => $response->body()]);
            throw new Exception("Falha ao gerar conteúdo com DeepSeek.");
        }

        $data = $response->json();
        $content = json_decode($data['choices'][0]['message']['content'], true);

        if (!isset($content['caption']) || !isset($content['image_prompt'])) {
            throw new Exception("Formato de resposta inválido do DeepSeek.");
        }

        return $content;
    }

    /**
     * Gera a imagem usando Together AI (FLUX.1-schnell).
     */
    public function generateImage(string $imagePrompt): string
    {
        if (!$this->togetherKey) {
            throw new Exception("Together AI API Key não configurada no sistema.");
        }

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer ' . $this->togetherKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.together.xyz/v1/images/generations', [
            'model'  => 'black-forest-labs/FLUX.1-schnell',
            'prompt' => $imagePrompt,
            'steps'  => 4,
            'n'      => 1,
            'width'  => 1024,
            'height' => 1024,
        ]);

        if (!$response->successful()) {
            Log::error("Together AI API Error", ['response' => $response->body()]);
            throw new Exception("Falha ao gerar imagem com Together AI.");
        }

        $data = $response->json();
        $imageUrl = $data['data'][0]['url'] ?? null;

        if (!$imageUrl) {
            throw new Exception("A URL da imagem não foi retornada pela Together AI.");
        }

        return $imageUrl;
    }
}
