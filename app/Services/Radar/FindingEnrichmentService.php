<?php

namespace App\Services\Radar;

use App\Models\RadarFinding;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

class FindingEnrichmentService
{
    private const AREAS_CONHECIDAS = [
        'assistência social', 'criança e adolescente', 'idoso', 'pessoa com deficiência',
        'mulher', 'juventude', 'saúde', 'educação', 'cultura', 'esporte',
        'meio ambiente', 'habitação', 'segurança alimentar', 'geração de renda',
        'direitos humanos', 'esporte e lazer', 'inclusão digital',
    ];

    public function __construct(private DeepSeekService $deepseek) {}

    public function enrich(RadarFinding $finding): bool
    {
        if ($finding->ai_processed_at !== null) {
            return false;
        }

        $excerpt = mb_substr($finding->excerpt ?? '', 0, 1500);

        if (empty(trim($excerpt))) {
            $finding->update(['ai_processed_at' => now()]);
            return false;
        }

        $result = $this->callDeepSeek($excerpt);

        if ($result === null) {
            Log::warning("FindingEnrichmentService: DeepSeek retornou nulo para finding #{$finding->id}.");
            return false;
        }

        $finding->update([
            'areas'           => $result['areas'] ?: null,
            'object_summary'  => $result['object_summary'] ?: null,
            'deadline'        => $this->parseDate($result['deadline'] ?? null),
            'value_total'     => $this->parseDecimal($result['value_total'] ?? null),
            'is_relevant'     => (bool) ($result['is_relevant'] ?? false),
            'ai_processed_at' => now(),
        ]);

        return true;
    }

    private function callDeepSeek(string $excerpt): ?array
    {
        $areasStr = implode(', ', self::AREAS_CONHECIDAS);

        $prompt = "Você é um classificador de editais públicos brasileiros.\n"
            . "Analise o trecho abaixo e extraia APENAS informações explicitamente presentes no texto.\n"
            . "REGRA ABSOLUTA: se o dado não estiver claramente no trecho, retorne null. NUNCA invente prazo, valor ou área.\n\n"
            . "Trecho:\n{$excerpt}\n\n"
            . "Responda EXCLUSIVAMENTE com JSON válido, sem markdown, sem explicações:\n"
            . "{\n"
            . "  \"areas\": [\"lista de areas tematicas presentes no texto\"],\n"
            . "  \"object_summary\": \"resumo em 1-2 frases do objeto do chamamento, ou null\",\n"
            . "  \"deadline\": \"AAAA-MM-DD ou null se prazo nao mencionado\",\n"
            . "  \"value_total\": numero_decimal_ou_null,\n"
            . "  \"is_relevant\": true_ou_false\n"
            . "}\n\n"
            . "Áreas possíveis (use estas quando aplicável): {$areasStr}.\n"
            . "is_relevant = true se o texto menciona chamamento, convocação, edital ou parceria para OSCs/ONGs.";

        $response = $this->deepseek->chat(
            [['role' => 'user', 'content' => $prompt]],
            'deepseek-v4-flash',
            null,
            null,
        );

        if (isset($response['error'])) {
            Log::warning('FindingEnrichmentService: DeepSeek erro.', ['error' => $response['error']]);
            return null;
        }

        $content = $response['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return null;
        }

        // Strip markdown code blocks if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $parsed = json_decode(trim($clean), true);

        if (!is_array($parsed)) {
            Log::warning('FindingEnrichmentService: JSON inválido.', ['raw' => mb_substr($content, 0, 300)]);
            return null;
        }

        return $parsed;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value || $value === 'null') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function parseDecimal(mixed $value): ?string
    {
        if ($value === null || $value === 'null' || $value === '') {
            return null;
        }

        $num = filter_var($value, FILTER_VALIDATE_FLOAT);

        return $num !== false ? (string) $num : null;
    }
}
