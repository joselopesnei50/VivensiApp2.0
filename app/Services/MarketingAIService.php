<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;

class MarketingAIService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Generate a complete marketing strategy based on a goal and audience.
     */
    public function generateStrategy($goal, $audience)
    {
        $prompt = "
        Atue como um Especialista em Marketing Digital e Copywriter Sênior.
        
        OBJETIVO: {$goal}
        PÚBLICO-ALVO: {$audience}

        Sua tarefa é gerar um **JSON estrito** (sem markdown, apenas o json) com duas seções principais:

        1. 'social': Um array com 3 ideias de posts para redes sociais (Instagram/LinkedIn).
           Cada post deve ter:
           - 'title': Manchete chamativa.
           - 'caption': Legenda completa e engajadora com emojis e hashtags.
           - 'image_keyword': Uma palavra-chave visual em INGLÊS para buscar no Unsplash (ex: 'teamwork office', 'happy children').

        2. 'landing_page': Textos persuasivos para uma página de conversão, baseados no método AIDA.
           Deve ter:
           - 'hero_headline': Título principal curto e impactante (H1).
           - 'hero_subheadline': Subtítulo explicativo (H2).
           - 'cta_button': Texto do botão de ação.
           - 'benefits_title': Título para a seção de benefícios.
           - 'benefits_list': Um array com 3 frases curtas de benefícios/vantagens.
           - 'about_title': Título para a seção 'Sobre Nós' ou 'A Causa'.
           - 'about_text': Um parágrafo de 3 linhas resumindo a proposta de valor.

        Retorne APENAS o JSON válido.
        ";

        try {
            // Chamada ao Gemini
            $response = $this->gemini->generateText($prompt);
            
            // Limpeza básica para garantir que é JSON puro (remover blocos de código markdown se houver)
            $jsonString = $this->cleanJson($response);

            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Erro no JSON da IA: " . json_last_error_msg());
                Log::debug("Resposta bruta: " . $response);
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Erro ao gerar estratégia de marketing: " . $e->getMessage());
            return null;
        }
    }

    private function cleanJson($text)
    {
        // Remove marcadores de código markdown ```json ... ```
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        return trim($text);
    }
}
