<?php

namespace App\Services\Blog;

use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BlogPostGeneratorService
{
    public const CURATED_THEMES = [
        'lgpd-terceiro-setor'        => 'LGPD no Terceiro Setor: cuidados com dados de beneficiários',
        'cebas-passo-a-passo'        => 'CEBAS: como obter e manter a certificação da sua ONG',
        'mrosc-parcerias'            => 'MROSC: como preparar sua ONG para parcerias públicas',
        'editais-captacao'           => 'Como captar recursos via editais públicos',
        'prestacao-de-contas'        => 'Prestação de contas transparente para ONGs',
        'voluntariado-gestao'        => 'Gestão de voluntários: engajamento e retenção',
        'impacto-social-mensuracao'  => 'Como medir o impacto social do seu projeto',
        'suas-conselhos'             => 'SUAS e conselhos: navegando a rede socioassistencial',
        'compliance-ong'             => 'Compliance no Terceiro Setor: fugindo dos riscos jurídicos',
        'planejamento-estrategico'   => 'Planejamento estratégico para ONGs pequenas',
        'comunicacao-doadores'       => 'Comunicação com doadores via WhatsApp: boas práticas',
        'lista-presenca-projetos'    => 'Como controlar frequência em projetos socioassistenciais',
        'financiamento-recorrente'   => 'Doação recorrente: construindo previsibilidade de caixa',
        'transparencia-portal'       => 'Portal da transparência: o que ONG obrigatoriamente publica',
        'radar-editais-tecnologia'   => 'Como a tecnologia acelera a busca por editais',
    ];

    private const VIVENSI_CONTEXT = <<<TXT
Contexto do Vivensi (SaaS para gestão de ONGs e Terceiro Setor):
- Radar de Editais: monitora automaticamente editais públicos (Querido Diário, MROSC)
- Motor de Conformidade Contínua: acompanha requisitos CEBAS, MROSC, SUAS
- Módulo LGPD self-service: /eu/dados para exportar/deletar dados de beneficiários
- Prestação de contas: relatórios PDF de projetos com metas e resultados
- WhatsApp Cloud API oficial: broadcast e chat com doadores/beneficiários
- Cadastro de beneficiários com anexos criptografados
- Turmas e lista de presença por projeto
- Kanban de projetos com aprovação por etapa
- Bruce AI: assistente contextual para consultas do dia a dia da ONG
Use esses módulos como referência prática quando fizer sentido, sem parecer publicidade.
TXT;

    public function __construct(private DeepSeekService $deepSeek) {}

    public static function themeChoices(): array
    {
        return self::CURATED_THEMES;
    }

    /**
     * Gera rascunho de post para o blog institucional.
     *
     * @return array{title:string, excerpt:string, content:string, meta_description:string, tags:string}
     */
    public function generateDraft(string $theme): array
    {
        $theme = trim($theme);
        if ($theme === '') {
            throw new RuntimeException('Tema obrigatório.');
        }

        $themeLabel = self::CURATED_THEMES[$theme] ?? $theme;

        $system = "Você é redator especialista em Terceiro Setor brasileiro. Escreva em português do Brasil, tom informativo e acessível, sem jargão excessivo. Nunca use trial/gratuidade como isca comercial.\n\n" . self::VIVENSI_CONTEXT;

        $user = <<<PROMPT
Escreva um post curto de blog (~500 palavras) sobre: **{$themeLabel}**.

Retorne APENAS um objeto JSON válido, sem markdown, sem crase de código, com exatamente estes campos:
{
  "title": "título chamativo, até 90 caracteres",
  "excerpt": "resumo em 1-2 frases, até 200 caracteres",
  "content": "corpo HTML válido usando apenas <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>, <a href>. 4-6 parágrafos, ~500 palavras. Sem <script>, <style>, <iframe>, sem inline handlers.",
  "meta_description": "descrição SEO até 155 caracteres",
  "tags": "3-5 tags separadas por vírgula, minúsculas, sem hashtag"
}
PROMPT;

        $response = $this->deepSeek->chat(
            messages: [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            model: 'deepseek-v4-flash',
            tenantId: null,
        );

        if (isset($response['error'])) {
            throw new RuntimeException($response['error']);
        }

        $raw = $response['choices'][0]['message']['content'] ?? '';
        if ($raw === '') {
            Log::warning('BlogPostGenerator: resposta vazia do DeepSeek', ['theme' => $theme]);
            throw new RuntimeException('IA retornou resposta vazia. Tente novamente.');
        }

        $json = $this->extractJson($raw);
        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['title'], $data['content'])) {
            Log::warning('BlogPostGenerator: JSON inválido do DeepSeek', [
                'theme'   => $theme,
                'preview' => mb_substr($raw, 0, 300),
            ]);
            throw new RuntimeException('IA retornou formato inesperado. Tente novamente.');
        }

        return [
            'title'            => mb_substr((string) $data['title'], 0, 255),
            'excerpt'          => mb_substr((string) ($data['excerpt'] ?? ''), 0, 500),
            'content'          => (string) $data['content'],
            'meta_description' => mb_substr((string) ($data['meta_description'] ?? ''), 0, 255),
            'tags'             => mb_substr((string) ($data['tags'] ?? ''), 0, 255),
        ];
    }

    /**
     * Modelo às vezes envolve JSON em ```json ... ```. Extrai só o objeto.
     */
    private function extractJson(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $m)) {
            return $m[1];
        }
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($raw, $start, $end - $start + 1);
        }
        return $raw;
    }
}
