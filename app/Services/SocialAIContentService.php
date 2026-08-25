<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\AiImageUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Cota mensal padrao de imagens por usuario. Configuravel via
     * SystemSetting 'social_ai_monthly_quota' sem precisar de deploy.
     */
    public const DEFAULT_MONTHLY_QUOTA = 30;

    /**
     * Retorna a cota mensal vigente — SystemSetting override + fallback const.
     */
    public static function getMonthlyQuota(): int
    {
        $configured = SystemSetting::getValue('social_ai_monthly_quota');
        $q = is_numeric($configured) ? (int) $configured : self::DEFAULT_MONTHLY_QUOTA;
        return $q > 0 ? $q : self::DEFAULT_MONTHLY_QUOTA;
    }

    /**
     * Valida se o usuário ainda possui cota para o mês atual.
     */
    public function validateQuota(int $userId)
    {
        $limit = self::getMonthlyQuota();
        $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
        if ($currentUsage >= $limit) {
            throw new Exception("Limite mensal de {$limit} imagens atingido. Sua cota será renovada no próximo mês.");
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

        // Compat: aceita tanto 'captions' (novo formato) quanto 'caption' (formato antigo).
        // NORMALIZA cada item — DeepSeek as vezes devolve elemento como objeto aninhado
        // ({"text":"..."} em vez de string), o que fazia (string) trigger "Array to string
        // conversion" e virar literal "Array". self::flattenToString() cuida disso.
        if (isset($content['captions']) && is_array($content['captions'])) {
            $captions = array_values(array_filter(array_map(
                fn ($v) => self::flattenToString($v),
                $content['captions']
            )));
        } elseif (isset($content['caption'])) {
            $captions = array_filter([self::flattenToString($content['caption'])]);
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
        // DeepSeek tenha ignorado a instrução). Normaliza tb pra evitar cast
        // de array (bug reportado 2026-08-24).
        $imagePrompt = self::flattenToString($content['image_prompt']);
        if ($imagePrompt === '') {
            throw new Exception("Formato de resposta inválido do DeepSeek (image_prompt vazio).");
        }
        if ($visualStyle && isset(self::VISUAL_STYLES[$visualStyle])) {
            $imagePrompt .= '. Style: ' . self::VISUAL_STYLES[$visualStyle];
        }

        return [
            'captions'     => $captions,
            'image_prompt' => $imagePrompt,
        ];
    }

    /**
     * Converte qualquer valor do JSON do DeepSeek pra string segura, mesmo
     * quando o modelo devolveu objeto aninhado em vez do string esperado.
     *
     * Heuristicas: preferencia por chaves conhecidas (text, caption, content,
     * description, prompt); fallback: concatena strings escalares do array;
     * ultimo caso: json_encode.
     */
    public static function flattenToString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            // Array vazio nao tem string a extrair — devolve '' pra que o caller
            // detecte como invalido em vez de gerar prompt "[]" pra Together AI.
            if (empty($value)) {
                return '';
            }
            // Chaves comuns que o DeepSeek costuma usar quando aninha
            foreach (['text', 'caption', 'content', 'description', 'prompt', 'value'] as $k) {
                if (isset($value[$k]) && (is_string($value[$k]) || is_scalar($value[$k]))) {
                    return trim((string) $value[$k]);
                }
            }
            // Sem chave conhecida: junta strings escalares do array
            $parts = [];
            array_walk_recursive($value, function ($v) use (&$parts) {
                if (is_string($v) && trim($v) !== '') {
                    $parts[] = trim($v);
                } elseif (is_scalar($v)) {
                    $parts[] = (string) $v;
                }
            });
            if (!empty($parts)) {
                return implode(' ', $parts);
            }
            // Ultimo caso: json (evita "Array" literal chegar no modelo de imagem)
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        return '';
    }

    /**
     * Modelo default de geracao de imagem no Together AI.
     *
     * NOTA (2026-08-24): a Together removeu 'black-forest-labs/FLUX.1-schnell'
     * do tier serverless — hoje devolve 'model_not_available' com pedido pra
     * criar endpoint dedicado (caro). A versao '-Free' continua serverless
     * e gratuita. Configuravel via SystemSetting 'together_image_model' pra
     * caso a Together mude o nome/tier de novo sem exigir redeploy.
     */
    public const DEFAULT_IMAGE_MODEL = 'black-forest-labs/FLUX.1-schnell-Free';

    /**
     * Gera a imagem usando Together AI (default FLUX.1-schnell-Free serverless).
     *
     * $format: 'square' (1024x1024) ou 'story' (768x1344).
     */
    public function generateImage(string $imagePrompt, string $format = 'square'): string
    {
        if (!$this->togetherKey) {
            throw new Exception("Together AI API Key não configurada no sistema.");
        }

        $dims  = self::FORMATS[$format] ?? self::FORMATS['square'];
        $model = SystemSetting::getValue('together_image_model') ?: self::DEFAULT_IMAGE_MODEL;

        // steps otimizado por familia — FLUX Schnell foi treinado pra 4 steps;
        // SDXL e outros precisam de 25-30 pra nao sair borrado.
        $steps = str_contains($model, 'FLUX.1-schnell') ? 4 : 30;

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer ' . $this->togetherKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.together.xyz/v1/images/generations', [
            'model'  => $model,
            'prompt' => $imagePrompt,
            'steps'  => $steps,
            'n'      => 1,
            'width'  => $dims['width'],
            'height' => $dims['height'],
        ]);

        if (!$response->successful()) {
            Log::error("Together AI API Error", ['response' => $response->body(), 'model' => $model]);
            // Mensagem mais util pro user quando o modelo saiu do tier serverless
            $body = $response->json();
            $code = $body['error']['code'] ?? null;
            if ($code === 'model_not_available') {
                throw new Exception(
                    "Modelo de imagem {$model} nao esta mais disponivel na Together AI. " .
                    "Configure outro em /admin/settings (chave 'together_image_model') " .
                    "— sugestao: black-forest-labs/FLUX.1-schnell-Free."
                );
            }
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
