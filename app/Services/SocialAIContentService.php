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

    /** Estilos visuais aceitos + modificador de prompt em inglês pro FLUX.1. */
    public const VISUAL_STYLES = [
        'photorealistic' => 'high quality photography, cinematic lighting, sharp focus, professional composition',
        'illustration'   => 'digital illustration, vibrant colors, clean vector art style',
        'cartoon'        => 'playful cartoon style, bold outlines, expressive characters',
        'corporate'      => 'professional corporate photography, minimalist background, neutral colors',
        'minimalist'     => 'minimalist design, clean composition, lots of negative space, simple palette',
        'watercolor'     => 'watercolor painting, artistic brushstrokes, soft edges',
    ];

    /** Formatos aceitos e suas dimensões (compatíveis com FLUX.1-schnell). */
    public const FORMATS = [
        'square' => ['width' => 1024, 'height' => 1024, 'label' => 'Feed 1:1'],
        'story'  => ['width' => 768,  'height' => 1344, 'label' => 'Story 9:16'],
    ];

    /**
     * Gera 3 variações de legenda e 1 prompt de imagem usando DeepSeek.
     *
     * Retorno: ['captions' => [...3 strings], 'image_prompt' => string]
     */
    public function generateContent(string $theme, ?string $userContext = null, ?string $visualStyle = null, string $format = 'square'): array
    {
        if (!$this->deepseekKey) {
            throw new Exception("DeepSeek API Key não configurada no sistema.");
        }

        $contextBlock = $userContext
            ? "\n\nInstruções adicionais fornecidas pelo usuário (siga-as com prioridade):\n\"{$userContext}\""
            : '';

        $styleHint = $visualStyle && isset(self::VISUAL_STYLES[$visualStyle])
            ? "\n\nEstilo visual escolhido pelo usuário: {$visualStyle}. Incorpore esse tom no prompt de imagem."
            : '';

        $formatHint = ($format === 'story')
            ? "\n\nO post será usado como Story vertical (9:16) do Instagram — pense em composição vertical."
            : "\n\nO post será usado no feed quadrado (1:1) do Instagram/Facebook.";

        $prompt = "Atue como um especialista em marketing digital. Crie um post para rede social sobre o tema: '{$theme}'.{$contextBlock}{$styleHint}{$formatHint}

Retorne OBRIGATORIAMENTE um JSON com os seguintes campos:
- 'captions': ARRAY com EXATAMENTE 3 variações de legenda em português, cada uma com abordagem diferente (ex: emocional, direta, informativa). Cada legenda deve ser persuasiva, com emojis e respeitar qualquer instrução de tom, público ou detalhe fornecido.
- 'image_prompt': Um prompt descritivo detalhado em INGLÊS para gerar uma imagem de alta qualidade sobre este tema, considerando o estilo visual e formato escolhidos.

Responda apenas o JSON puro, sem blocos de código markdown.";

        // deepseek-v4-flash: legenda + prompt de imagem em JSON eh task simples
        // e curta — flash entrega com qualidade suficiente e custo baixo.
        // Substitui o alias 'deepseek-chat' (deprecado em 2026/07/24 pela DeepSeek).
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $this->deepseekKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => 'deepseek-v4-flash',
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

        // Compat: aceita tanto 'captions' (novo formato) quanto 'caption' (formato antigo)
        if (isset($content['captions']) && is_array($content['captions'])) {
            $captions = array_values(array_filter(array_map('strval', $content['captions'])));
        } elseif (isset($content['caption'])) {
            $captions = [(string) $content['caption']];
        } else {
            throw new Exception("Formato de resposta inválido do DeepSeek (sem captions).");
        }

        if (empty($captions) || !isset($content['image_prompt'])) {
            throw new Exception("Formato de resposta inválido do DeepSeek.");
        }

        // Garantir 3 legendas (se DeepSeek voltou menos, replica a última)
        while (count($captions) < 3) {
            $captions[] = end($captions);
        }
        $captions = array_slice($captions, 0, 3);

        // Injetar modificador de estilo no image_prompt (por segurança — caso
        // DeepSeek tenha ignorado a instrução).
        $imagePrompt = (string) $content['image_prompt'];
        if ($visualStyle && isset(self::VISUAL_STYLES[$visualStyle])) {
            $imagePrompt .= '. Style: ' . self::VISUAL_STYLES[$visualStyle];
        }

        return [
            'captions'     => $captions,
            'image_prompt' => $imagePrompt,
        ];
    }

    /**
     * Gera a imagem usando Together AI (FLUX.1-schnell).
     *
     * $format: 'square' (1024x1024) ou 'story' (768x1344).
     */
    public function generateImage(string $imagePrompt, string $format = 'square'): string
    {
        if (!$this->togetherKey) {
            throw new Exception("Together AI API Key não configurada no sistema.");
        }

        $dims = self::FORMATS[$format] ?? self::FORMATS['square'];

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer ' . $this->togetherKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.together.xyz/v1/images/generations', [
            'model'  => 'black-forest-labs/FLUX.1-schnell',
            'prompt' => $imagePrompt,
            'steps'  => 4,
            'n'      => 1,
            'width'  => $dims['width'],
            'height' => $dims['height'],
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
