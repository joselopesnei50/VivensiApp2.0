<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Gerador de template de e-mail via DeepSeek.
 *
 * Envolve o DeepSeekService com um system prompt especialista em email marketing
 * (copywriter + designer HTML) e parseia a resposta em JSON estrito.
 *
 * Retorno de sucesso: ['subject' => string, 'preheader' => string, 'html' => string]
 * Retorno de erro:    ['error' => string, 'error_code' => string]
 */
class EmailTemplateAiGeneratorService
{
    public function __construct(protected DeepSeekService $deepSeek)
    {
    }

    /**
     * @param array $input {
     *   brief:        string  descricao livre do que a campanha deve dizer
     *   tone?:        string  (ex.: "profissional", "casual", "urgente", "emotivo")
     *   audience?:    string  (ex.: "doadores da ONG", "clientes MEI")
     *   cta?:         string  chamada para acao (ex.: "doar agora")
     *   sender_name?: string  nome da organizacao/empresa remetente
     *   brand_color?: string  hexadecimal (ex.: #6366f1) — se ausente, usa padrao neutro
     * }
     */
    public function generate(array $input, int $tenantId): array
    {
        $brief = trim((string) ($input['brief'] ?? ''));
        if ($brief === '') {
            return [
                'error'      => 'Descreva o objetivo da campanha para a IA gerar o template.',
                'error_code' => 'missing_brief',
            ];
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user',   'content' => $this->userPrompt($input)],
        ];

        $response = $this->deepSeek->chat($messages, 'deepseek-v4-pro', null, $tenantId);

        if (isset($response['error'])) {
            return $response;
        }

        $raw = $response['choices'][0]['message']['content'] ?? '';
        $parsed = $this->extractJson($raw);

        if ($parsed === null) {
            Log::warning('EmailTemplateAiGenerator: resposta sem JSON valido', [
                'tenant_id'   => $tenantId,
                'raw_preview' => mb_substr($raw, 0, 500),
            ]);
            return [
                'error'      => 'A IA retornou um formato inesperado. Tente reescrever a descricao com mais detalhes.',
                'error_code' => 'parse_error',
            ];
        }

        $subject   = trim((string) ($parsed['subject']   ?? ''));
        $preheader = trim((string) ($parsed['preheader'] ?? ''));
        $html      = trim((string) ($parsed['html']      ?? ''));

        if ($subject === '' || $html === '') {
            return [
                'error'      => 'A IA nao devolveu um template completo. Tente novamente com uma descricao mais rica.',
                'error_code' => 'incomplete_response',
            ];
        }

        return [
            'subject'   => mb_substr(preg_replace('/[\r\n]+/', ' ', $subject), 0, 255),
            'preheader' => mb_substr(preg_replace('/[\r\n]+/', ' ', $preheader), 0, 150),
            'html'      => $html,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Voce e um especialista senior em email marketing (copywriter + designer HTML) com 10+ anos criando campanhas de alta conversao no Brasil. Voce domina:

- Linhas de assunto que passam por filtros de spam e geram taxa de abertura acima de 30%
- Preheader (texto de previa) complementar ao assunto, nunca redundante
- HTML compativel com Gmail, Outlook (2007-2019), Apple Mail e clientes moveis
- Layout mobile-first (600px de largura maxima, uma coluna, fontes >=14px)
- CSS inline (jamais <style> ou classes) — clientes de email nao suportam
- Uso exclusivo de <table> para estrutura (flex/grid nao funcionam no Outlook)
- CTA unico e destacado (botao com padding generoso, cor de marca, texto imperativo)
- Hierarquia visual: heading forte, paragrafo curto, prova/beneficio, CTA
- Copy PT-BR objetiva, humana, sem jargao corporativo
- Rodape com dados do remetente + link/instrucao de descadastro (LGPD)

REGRAS RIGOROSAS DE SAIDA:
1. Responda SOMENTE com um objeto JSON valido, sem markdown, sem crase, sem comentarios.
2. Estrutura obrigatoria:
   {
     "subject":   "string, ate 90 caracteres, sem emojis excessivos",
     "preheader": "string, ate 120 caracteres, complementa o subject",
     "html":      "string, HTML completo comecando em <!DOCTYPE html> e terminando em </html>"
   }
3. NUNCA use <script>, <iframe>, imagens externas obrigatorias (use apenas texto+cores), links para dominios suspeitos ou trackers de terceiros.
4. NUNCA invente urls reais — se precisar de link de CTA, use href="#" e comente ao redor pra o usuario preencher.
5. NUNCA repita placeholders tipo {{ nome }} de forma que quebre — use o nome do remetente literal que o usuario forneceu.
6. Rodape do e-mail deve conter: nome do remetente + copyright do ano corrente + linha "Se nao deseja mais receber nossos e-mails, responda com o assunto DESCADASTRAR".
7. O HTML deve renderizar bem em modo escuro (evite fundos brancos puros para textos escuros; prefira #f8fafc + #1e293b).

Se a descricao do usuario for ambigua, faca a melhor interpretacao possivel — nao pergunte de volta.
PROMPT;
    }

    private function userPrompt(array $input): string
    {
        $brief      = trim((string) ($input['brief'] ?? ''));
        $tone       = trim((string) ($input['tone'] ?? 'profissional e acolhedor'));
        $audience   = trim((string) ($input['audience'] ?? 'destinatarios gerais'));
        $cta        = trim((string) ($input['cta'] ?? ''));
        $senderName = trim((string) ($input['sender_name'] ?? ''));
        $brandColor = trim((string) ($input['brand_color'] ?? ''));

        $lines = [
            "Descricao da campanha:",
            $brief,
            '',
            "Tom desejado: {$tone}",
            "Publico-alvo: {$audience}",
        ];

        if ($cta !== '') {
            $lines[] = "Chamada para acao (CTA principal): {$cta}";
        }
        if ($senderName !== '') {
            $lines[] = "Nome do remetente (usar no cabecalho e rodape): {$senderName}";
        }
        if ($brandColor !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
            $lines[] = "Cor primaria da marca (usar no header e no botao CTA): {$brandColor}";
        }

        $lines[] = '';
        $lines[] = 'Gere agora o JSON conforme instruido no system prompt.';

        return implode("\n", $lines);
    }

    /**
     * DeepSeek as vezes envolve o JSON em ```json ... ``` ou coloca texto antes/depois.
     * Extrai o primeiro objeto JSON balanceado da resposta.
     */
    private function extractJson(string $raw): ?array
    {
        $trimmed = trim($raw);
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Remove fences markdown ```json ... ``` se presentes
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback: primeiro { ate o ultimo } equilibrado
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($raw, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
