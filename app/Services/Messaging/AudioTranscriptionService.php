<?php

namespace App\Services\Messaging;

use App\Models\WhatsappMessage;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AudioTranscriptionService — Fase 4 (item 2.4 do roadmap).
 *
 * Transcreve áudios do WhatsApp via Gemini multimodal nativo. O endpoint
 * pode receber base64 vindo do front (quando temos cache do payload) ou
 * tentar baixar de media_path. Persiste a transcrição em
 * whatsapp_messages.transcription pra evitar custo recorrente.
 *
 * Cobre só transcrição (texto bruto). A resposta da IA continua sendo
 * gerada pelo ProcessWhatsappAiResponse — esse Service NÃO substitui o bot,
 * só dá visibilidade ao atendente sobre o que o contato disse.
 */
class AudioTranscriptionService
{
    public const MAX_AUDIO_BYTES = 8 * 1024 * 1024; // 8 MB — Gemini limita.

    public function __construct(private GeminiService $gemini)
    {
    }

    /**
     * Transcreve um áudio cru e devolve {text, provider, error}.
     * Não persiste em DB.
     */
    public function transcribe(string $base64Audio, string $mimeType = 'audio/ogg'): array
    {
        $bytes = strlen(base64_decode($base64Audio, true) ?: '');
        if ($bytes === 0) {
            return $this->fail('Base64 inválido ou vazio.');
        }
        if ($bytes > self::MAX_AUDIO_BYTES) {
            return $this->fail('Áudio acima do limite de ' . (self::MAX_AUDIO_BYTES >> 20) . ' MB.');
        }

        try {
            $res = $this->gemini->callGemini([
                ['text' => 'Transcreva integralmente este áudio para texto em português brasileiro. Devolva APENAS a transcrição, sem prefácio, sem aspas, sem comentários. Se o áudio estiver inaudível ou vazio, responda exatamente: [inaudível]'],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Audio]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('AudioTranscription: Gemini falhou', ['error' => $e->getMessage()]);
            return $this->fail('Falha ao chamar Gemini.');
        }

        if (isset($res['error'])) {
            return $this->fail('Gemini reportou erro: ' . (is_string($res['error']) ? $res['error'] : 'desconhecido'));
        }

        $text = (string) data_get($res, 'candidates.0.content.parts.0.text', '');
        $text = $this->normalize($text);
        if ($text === '') {
            return $this->fail('Resposta vazia do provedor.');
        }

        return [
            'text'     => $text,
            'provider' => 'gemini',
            'error'    => null,
        ];
    }

    /**
     * Transcreve e PERSISTE em whatsapp_messages.transcription. Idempotente:
     * se já existe transcrição, retorna direto sem chamar o provedor.
     *
     * @param string|null $base64Audio  preferido (vem do front quando disponível)
     */
    public function transcribeMessage(WhatsappMessage $message, ?string $base64Audio = null): array
    {
        if ($message->type !== 'audio') {
            return $this->fail('Mensagem não é áudio.');
        }

        if (!empty($message->transcription)) {
            return [
                'text'     => $message->transcription,
                'provider' => 'cache',
                'error'    => null,
            ];
        }

        if ($base64Audio === null || $base64Audio === '') {
            $base64Audio = $this->fetchBase64FromMediaPath($message->media_path);
        }
        if ($base64Audio === null) {
            return $this->fail('Áudio não disponível para transcrição (URL expirada ou ausente).');
        }

        $result = $this->transcribe($base64Audio);
        if ($result['error'] === null) {
            $message->update(['transcription' => $result['text']]);
        }
        return $result;
    }

    /**
     * Tenta baixar a URL do áudio e devolver base64. Usado quando o front
     * não passou o blob direto. URLs do Baileys expiram — falhas são
     * normais e tratadas como áudio indisponível.
     */
    private function fetchBase64FromMediaPath(?string $url): ?string
    {
        if (!is_string($url) || $url === '') {
            return null;
        }
        try {
            $res = Http::timeout(15)->get($url);
            if (!$res->successful()) {
                return null;
            }
            $bytes = $res->body();
            if ($bytes === '' || strlen($bytes) > self::MAX_AUDIO_BYTES) {
                return null;
            }
            return base64_encode($bytes);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalize(string $raw): string
    {
        $text = trim($raw);
        // LLM costuma embrulhar a transcrição em aspas — remove.
        if (preg_match('/^"(.*)"$/su', $text, $m)) {
            $text = $m[1];
        }
        // Strip prefácio comum ("Transcrição:", "O áudio diz:", etc).
        $text = preg_replace('/^(transcri[cç][aã]o|o [aá]udio diz|texto)\s*:\s*/iu', '', $text) ?? $text;
        return trim($text);
    }

    private function fail(string $reason): array
    {
        return [
            'text'     => '',
            'provider' => 'gemini',
            'error'    => $reason,
        ];
    }
}
